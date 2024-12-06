<?php
require_once 'crud.php';

class Dashboard
{
    private $database;
    private $conn;
    private $crud;

    public function __construct()
    {
        $this->database = new Database();
        $this->conn =  $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    public function getUsername($user_id)
    {
        return $this->crud->readSingleRow(
            'SELECT first_name FROM users WHERE user_id = ?',
            [$user_id],
            'i'
        )['first_name'];
    }

    public function createCourse($course_name, $teacher_id, $assignment_weight, $quiz_weight, $exam_weight)
    {
        return $this->crud->createRecord(
            'INSERT INTO courses (course_name, teacher_id, assignment_weight, quiz_weight, exam_weight) VALUES (?, ?, ?, ?, ?)',
            [$course_name, $teacher_id, $assignment_weight, $quiz_weight, $exam_weight],
            'siiii'
        );
    }

    public function getCourses($teacher_id)
    {
        return $this->crud->readAllRows(
            'SELECT * FROM courses WHERE teacher_id = ?',
            [$teacher_id],
            'i'
        );
    }

    public function getCourseDetails($course_id)
    {
        return $this->crud->readAllRows(
            'SELECT * FROM courses WHERE course_id = ?',
            [$course_id],
            'i'
        );
    }









    //---------StudentDAsh 
    public function getStudentCourse($student_id)
    {
        // SQL query to fetch courses that the student is enrolled in 
        $sql = 'SELECT c.* 
                FROM courses c
                JOIN student_courses sc ON c.course_id = sc.course_id
                WHERE  sc.student_id = ?';
                
    
        return $this->crud->readAllRows($sql, [ $student_id], 'i');
    }

}
