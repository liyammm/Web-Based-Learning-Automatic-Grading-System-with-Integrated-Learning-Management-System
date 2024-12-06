<?php
require_once 'crud.php';

class Chart {
    private $database;
    private $conn;
    private $crud;

    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    // Count graded submissions for a specific assignment
    public function countGradedSubmissions($assignment_id) {
        $query = "SELECT COUNT(DISTINCT as1.student_id) AS graded 
                 FROM assignment_submissions as1
                 JOIN student_courses sc ON sc.student_id = as1.student_id
                 JOIN assignments a ON a.assignment_id = as1.assignment_id AND a.course_id = sc.course_id
                 WHERE as1.assignment_id = ? AND as1.grade IS NOT NULL";
        $params = [$assignment_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return $result ? $result['graded'] : 0;
    }

    // Count not graded submissions for a specific assignment
    public function countNotGradedSubmissions($assignment_id) {
        $query = "SELECT COUNT(DISTINCT as1.student_id) AS not_graded 
                 FROM assignment_submissions as1
                 JOIN student_courses sc ON sc.student_id = as1.student_id
                 JOIN assignments a ON a.assignment_id = as1.assignment_id AND a.course_id = sc.course_id
                 WHERE as1.assignment_id = ? AND as1.grade IS NULL";
        $params = [$assignment_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return $result ? $result['not_graded'] : 0;
    }

    // Count students who have not submitted a specific assignment
    public function countNotSubmittedSubmissions($assignment_id) {
        $query = "SELECT COUNT(DISTINCT sc.student_id) AS not_submitted 
                 FROM student_courses sc
                 JOIN assignments a ON a.course_id = sc.course_id
                 WHERE a.assignment_id = ?
                 AND sc.student_id NOT IN (
                     SELECT student_id 
                     FROM assignment_submissions 
                     WHERE assignment_id = ?
                 )";
        $params = [$assignment_id, $assignment_id];
        $result = $this->crud->readSingleRow($query, $params, 'ii');
        return $result ? $result['not_submitted'] : 0;
    }

    // Count passed submissions for a specific assignment
    public function countPassedSubmissions($assignment_id, $totalPoints) {
        $passingScore = 0.75 * $totalPoints;
        $query = "SELECT COUNT(*) AS passed FROM assignment_submissions WHERE assignment_id = ? AND grade >= ?";
        $params = [$assignment_id, $passingScore];
        $result = $this->crud->readSingleRow($query, $params, 'id');
        return $result ? $result['passed'] : 0;
    }
    
    // Count failed submissions for a specific assignment
    public function countFailedSubmissions($assignment_id, $totalPoints) {
        $passingScore = 0.75 * $totalPoints;
        $query = "SELECT COUNT(*) AS failed FROM assignment_submissions WHERE assignment_id = ? AND grade < ?";
        $params = [$assignment_id, $passingScore];
        $result = $this->crud->readSingleRow($query, $params, 'id');
        return $result ? $result['failed'] : 0;
    }




    //for assessment
    public function countGradedAttempts($assessment_id) {
        $query = "SELECT COUNT(*) AS graded 
                  FROM assessment_attempts 
                  WHERE assessment_id = ? 
                  AND status = 'completed' 
                  AND score IS NOT NULL";
        $params = [$assessment_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return $result ? $result['graded'] : 0;
    }

    public function countNotGradedAttempts($assessment_id) {
        $query = "SELECT COUNT(*) AS not_graded 
                  FROM assessment_attempts 
                  WHERE assessment_id = ? 
                  AND status = 'completed' 
                  AND score IS NULL";
        $params = [$assessment_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return $result ? $result['not_graded'] : 0;
    }

    public function countNotAttemptedAssessments($assessment_id) {
        $query = "SELECT COUNT(DISTINCT sc.student_id) AS not_attempted
                  FROM student_courses sc
                  LEFT JOIN assessment_attempts aa 
                     ON sc.student_id = aa.student_id 
                     AND aa.assessment_id = ?
                  JOIN assessments a ON a.assessment_id = ?
                  WHERE sc.course_id = a.course_id 
                  AND aa.attempt_id IS NULL";
        $params = [$assessment_id, $assessment_id];
        $result = $this->crud->readSingleRow($query, $params, 'ii');
        return $result ? $result['not_attempted'] : 0;
    }

    public function countPassedAttempts($assessment_id, $total_points) {
        $passing_score = $total_points * 0.75; // 75% passing score
        $query = "SELECT COUNT(*) AS passed 
                  FROM assessment_attempts 
                  WHERE assessment_id = ? 
                  AND status = 'completed'
                  AND score >= ?";
        $params = [$assessment_id, $passing_score];
        $result = $this->crud->readSingleRow($query, $params, 'id');
        return $result ? $result['passed'] : 0;
    }

    public function countFailedAttempts($assessment_id, $total_points) {
        $passing_score = $total_points * 0.75; // 75% passing score
        $query = "SELECT COUNT(*) AS failed 
                  FROM assessment_attempts 
                  WHERE assessment_id = ? 
                  AND status = 'completed'
                  AND score < ?";
        $params = [$assessment_id, $passing_score];
        $result = $this->crud->readSingleRow($query, $params, 'id');
        return $result ? $result['failed'] : 0;
    }





    //for final grade
    //course for assessment staticstic
    public function getCourseAssignmentStats($course_id) {
        $query = "WITH student_assignments AS (
            SELECT 
                u.user_id,
                COALESCE(
                    (SELECT AVG(COALESCE((s.grade / a.total_points) * 100, 0))
                    FROM assignments a
                    LEFT JOIN assignment_submissions s ON s.assignment_id = a.assignment_id 
                        AND s.student_id = u.user_id
                    WHERE a.course_id = sc.course_id), 0
                ) as assignment_avg
            FROM users u
            JOIN student_courses sc ON u.user_id = sc.student_id
            WHERE sc.course_id = ?
        )
        SELECT 
            COUNT(CASE WHEN assignment_avg >= 75 THEN 1 END) as passed,
            COUNT(CASE WHEN assignment_avg < 75 AND assignment_avg > 0 THEN 1 END) as failed,
            COUNT(CASE WHEN assignment_avg = 0 THEN 1 END) as not_comply
        FROM student_assignments";
        
        $params = [$course_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return [
            'passed' => $result ? intval($result['passed']) : 0,
            'failed' => $result ? intval($result['failed']) : 0,
            'not_comply' => $result ? intval($result['not_comply']) : 0
        ];
    }

    // Course-wide Quiz Statistics
    public function getCourseQuizStats($course_id) {
        $query = "WITH student_quizzes AS (
            SELECT 
                u.user_id,
                COALESCE(
                    (SELECT SUM(CASE 
                        WHEN aa.score IS NOT NULL THEN (aa.score / a.total_points) * 100
                        ELSE 0 
                    END) / (
                        SELECT COUNT(*)
                        FROM assessments 
                        WHERE course_id = sc.course_id 
                        AND assessment_type = 'quiz'
                    )
                    FROM assessments a
                    LEFT JOIN assessment_attempts aa ON aa.assessment_id = a.assessment_id 
                        AND aa.student_id = u.user_id
                    WHERE a.course_id = sc.course_id
                    AND a.assessment_type = 'quiz'), 0
                ) as quiz_avg
            FROM users u
            JOIN student_courses sc ON u.user_id = sc.student_id
            WHERE sc.course_id = ?
        )
        SELECT 
            COUNT(CASE WHEN quiz_avg >= 75 THEN 1 END) as passed,
            COUNT(CASE WHEN quiz_avg < 75 AND quiz_avg > 0 THEN 1 END) as failed,
            COUNT(CASE WHEN quiz_avg = 0 THEN 1 END) as not_comply
        FROM student_quizzes";
        
        $params = [$course_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return [
            'passed' => $result ? intval($result['passed']) : 0,
            'failed' => $result ? intval($result['failed']) : 0,
            'not_comply' => $result ? intval($result['not_comply']) : 0
        ];
    }

    // Course-wide Exam Statistics
    public function getCourseExamStats($course_id) {
        $query = "WITH student_exams AS (
            SELECT 
                u.user_id,
                COALESCE(
                    (SELECT SUM(CASE 
                        WHEN aa.score IS NOT NULL THEN (aa.score / a.total_points) * 100
                        ELSE 0 
                    END) / (
                        SELECT COUNT(*)
                        FROM assessments 
                        WHERE course_id = sc.course_id 
                        AND assessment_type = 'exam'
                    )
                    FROM assessments a
                    LEFT JOIN assessment_attempts aa ON aa.assessment_id = a.assessment_id 
                        AND aa.student_id = u.user_id
                    WHERE a.course_id = sc.course_id
                    AND a.assessment_type = 'exam'), 0
                ) as exam_avg
            FROM users u
            JOIN student_courses sc ON u.user_id = sc.student_id
            WHERE sc.course_id = ?
        )
        SELECT 
            COUNT(CASE WHEN exam_avg >= 75 THEN 1 END) as passed,
            COUNT(CASE WHEN exam_avg < 75 AND exam_avg > 0 THEN 1 END) as failed,
            COUNT(CASE WHEN exam_avg = 0 THEN 1 END) as not_comply
        FROM student_exams";
        
        $params = [$course_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return [
            'passed' => $result ? intval($result['passed']) : 0,
            'failed' => $result ? intval($result['failed']) : 0,
            'not_comply' => $result ? intval($result['not_comply']) : 0
        ];
    }

    // Course-wide Final Grade Statistics
    public function getCourseFinalGradeStats($course_id) {
        $query = "WITH student_grades AS (
            SELECT 
                u.user_id,
                (
                    COALESCE(
                        (SELECT AVG(COALESCE((s.grade / a.total_points) * 100, 0))
                        FROM assignments a
                        LEFT JOIN assignment_submissions s ON s.assignment_id = a.assignment_id 
                            AND s.student_id = u.user_id
                        WHERE a.course_id = sc.course_id), 0
                    ) * (c.assignment_weight / 100) +
                    COALESCE(
                        (SELECT SUM(CASE 
                            WHEN aa.score IS NOT NULL THEN (aa.score / a.total_points) * 100
                            ELSE 0 
                        END) / (
                            SELECT COUNT(*)
                            FROM assessments 
                            WHERE course_id = sc.course_id 
                            AND assessment_type = 'quiz'
                        )
                        FROM assessments a
                        LEFT JOIN assessment_attempts aa ON aa.assessment_id = a.assessment_id 
                            AND aa.student_id = u.user_id
                        WHERE a.course_id = sc.course_id
                        AND a.assessment_type = 'quiz'), 0
                    ) * (c.quiz_weight / 100) +
                    COALESCE(
                        (SELECT SUM(CASE 
                            WHEN aa.score IS NOT NULL THEN (aa.score / a.total_points) * 100
                            ELSE 0 
                        END) / (
                            SELECT COUNT(*)
                            FROM assessments 
                            WHERE course_id = sc.course_id 
                            AND assessment_type = 'exam'
                        )
                        FROM assessments a
                        LEFT JOIN assessment_attempts aa ON aa.assessment_id = a.assessment_id 
                            AND aa.student_id = u.user_id
                        WHERE a.course_id = sc.course_id
                        AND a.assessment_type = 'exam'), 0
                    ) * (c.exam_weight / 100)
                ) as final_grade
            FROM users u
            JOIN student_courses sc ON u.user_id = sc.student_id
            JOIN courses c ON c.course_id = sc.course_id
            WHERE sc.course_id = ?
        )
        SELECT 
            COUNT(CASE WHEN final_grade >= 75 THEN 1 END) as passed,
            COUNT(CASE WHEN final_grade < 75 THEN 1 END) as failed
        FROM student_grades";
        
        $params = [$course_id];
        $result = $this->crud->readSingleRow($query, $params, 'i');
        return [
            'passed' => $result ? intval($result['passed']) : 0,
            'failed' => $result ? intval($result['failed']) : 0
        ];
    }
    
    
    
}