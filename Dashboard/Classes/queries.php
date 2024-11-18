<?php
require_once 'crud.php';


class Dashboard {
    private $database;
    private $conn;
    private $crud;

    public function __construct() {
        $this->database = new Database();
        $this->conn =  $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    public function getTeacherName($teacher_id) {
        return $this->crud->readSingleRow(
            'SELECT first_name FROM users WHERE user_id = ?', 
            [$teacher_id], 
            'i'
        )['first_name'];
    }

    public function createCourse($course_name, $teacher_id, $assignment_weight, $quiz_weight, $exam_weight) {
        return $this->crud->createRecord(
            'INSERT INTO courses (course_name, teacher_id, assignment_weight, quiz_weight, exam_weight) VALUES (?, ?, ?, ?, ?)', 
            [$course_name, $teacher_id, $assignment_weight, $quiz_weight, $exam_weight], 
            'siiii'
        );
    }

    public function deleteCourse($course_id) {
        return $this->crud->deleteRecord(
            'DELETE FROM courses WHERE course_id = ?', 
            [$course_id], 
            'i'
        );
    }

    public function getCourses($teacher_id) {
        return $this->crud->readAllRows(
            'SELECT * FROM courses WHERE teacher_id = ?', 
            [$teacher_id], 
            'i'
        );
    }

    public function getCourseDetails($course_id) {
        return $this->crud->readAllRows(
            'SELECT * FROM courses WHERE course_id = ?', 
            [$course_id], 
            'i'
        );
    }

    //ADD STUDENT SECTION
    public function addStudentToCourse($student_username, $course_id) {
        $student = $this->crud->readSingleRow(
            'SELECT user_id FROM users WHERE username = ?', 
            [$student_username], 
            's'
        );
        if ($student) {
            return $this->crud->createRecord(
                'INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)', 
                [$student['user_id'], $course_id], 
                'ii'
            );
        }
        return false;
    }

    public function getEnrolledStudents($course_id) {
        return $this->crud->readAllRows(
            'SELECT u.user_id, u.username, u.first_name, u.last_name, u.email
             FROM student_courses sc
             JOIN users u ON sc.student_id = u.user_id
             WHERE sc.course_id = ?', 
            [$course_id], 
            'i'
        );
    }

    public function unenrollStudent($student_id, $course_id) {
        return $this->crud->deleteRecord(
            'DELETE FROM student_courses WHERE student_id = ? AND course_id = ?', 
            [$student_id, $course_id], 
            'ii'
        );
    }


    //LEARNING MATERIALS SECTION
    // Upload learning material
    public function uploadLearningMaterial($course_id, $file_name, $description, $file_data) {
        return $this->crud->createRecord(
            'INSERT INTO learning_materials (course_id, file_name, description, file_data) VALUES (?, ?, ?, ?)', 
            [$course_id, $file_name, $description, $file_data], 
            'isss'
        );
    }

    // Get learning materials for a course
    public function getLearningMaterials($course_id) {
        return $this->crud->readAllRows(
            'SELECT learning_materials_id, file_name, description, upload_date 
             FROM learning_materials WHERE course_id = ?', 
            [$course_id], 
            'i'
        );
    }

    // Get file data for downloading
    public function getLearningMaterialData($material_id) {
        return $this->crud->readSingleRow(
            'SELECT file_name, file_data 
             FROM learning_materials WHERE learning_materials_id = ?', 
            [$material_id], 
            'i'
        );
    }

    // Delete learning material
    public function deleteLearningMaterial($material_id) {
        return $this->crud->deleteRecord(
            'DELETE FROM learning_materials WHERE learning_materials_id = ?', 
            [$material_id], 
            'i'
        );
    }

}

?>
