<?php
class GradeCalculator {
    private $database;
    private $conn;
    private $crud;

    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    public function calculateGrades($courseId) {
        $query = "SELECT 
            u.user_id, 
            u.first_name, 
            u.last_name,
            COALESCE(
                (SELECT AVG(COALESCE((s.grade / a.total_points) * 100, 0))
                FROM assignments a
                LEFT JOIN assignment_submissions s ON s.assignment_id = a.assignment_id 
                    AND s.student_id = u.user_id
                WHERE a.course_id = sc.course_id), 0
            ) as assignment_avg,
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
            ) as quiz_avg,
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
        GROUP BY u.user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calculate final grades for each student
        foreach ($results as &$grade) {
            $grade['assignment_avg'] = round($grade['assignment_avg'], 2);
            $grade['quiz_avg'] = round($grade['quiz_avg'], 2);
            $grade['exam_avg'] = round($grade['exam_avg'], 2);
        }

        return $results;
    }

    public function updateFinalGrades($courseId, $grades, $courseWeights) {
        foreach ($grades as &$grade) {
            // Normalize weights to percentages
            $assignment_weight = $courseWeights['assignment_weight'] / 100;
            $quiz_weight = $courseWeights['quiz_weight'] / 100;
            $exam_weight = $courseWeights['exam_weight'] / 100;
        
            // Calculate final grade using normalized weights
            $final_grade = 
                ($grade['assignment_avg'] * $assignment_weight) +
                ($grade['quiz_avg'] * $quiz_weight) +
                ($grade['exam_avg'] * $exam_weight);
            
            $grade['final_grade'] = round($final_grade, 2); // Round for display purposes
            $grade['status'] = $grade['final_grade'] >= 75 ? 'Passed' : 'Failed';
        
            // Update database
            $query = "INSERT INTO final_grades 
                    (course_id, student_id, assignment_average, quiz_average, exam_average, final_grade, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    assignment_average = VALUES(assignment_average),
                    quiz_average = VALUES(quiz_average),
                    exam_average = VALUES(exam_average),
                    final_grade = VALUES(final_grade),
                    status = VALUES(status)";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iidddds", 
                $courseId,
                $grade['user_id'],
                $grade['assignment_avg'],
                $grade['quiz_avg'],
                $grade['exam_avg'],
                $grade['final_grade'],
                $grade['status']
            );
            $stmt->execute();
        }
        
        return $grades; // Return the updated grades array
    }
}