<?php
require_once 'crud.php';

class Student
{
    private $database;
    private $conn;
    private $crud;

    public function __construct()
    {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    // ADD STUDENT SECTION
    public function enrollStudents($identifier, $course_id)
    {
        // Retrieve the student_id based on username or email
        $query = 'SELECT user_id FROM users WHERE username = ? OR email = ?';
        $params = [$identifier, $identifier];
        $student = $this->crud->readSingleRow($query, $params, 'ss');

        // If student exists, proceed to insert into student_courses
        if ($student) {
            $query = 'INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)';
            $params = [$student['user_id'], $course_id];
            return $this->crud->createRecord($query, $params, 'ii');
        }

        return false; // Student not found
    }

    public function getEnrolledStudents($course_id)
    {
        $query = 'SELECT u.user_id, u.username, u.first_name, u.last_name, u.email
                  FROM student_courses sc
                  JOIN users u ON sc.student_id = u.user_id
                  WHERE sc.course_id = ?';
        $params = [$course_id];
        return $this->crud->readAllRows($query, $params, 'i');
    }

    public function unenrollStudent($student_id, $course_id)
    {
        $query = 'DELETE FROM student_courses WHERE student_id = ? AND course_id = ?';
        $params = [$student_id, $course_id];
        return $this->crud->deleteRecord($query, $params, 'ii');
    }

    //for enrolling through email and username
    public function getUserByUsername($emailUsername)
    {
        $query = 'SELECT * FROM users WHERE username = ? OR email = ?';
        $params = [$emailUsername, $emailUsername];
        return $this->crud->readSingleRow($query, $params, 'ss');
    }

    //check if enrolled 
    public function checkStudentEnrolled($student_id, $course_id)
    {
        $query = 'SELECT * FROM student_courses WHERE student_id = ? AND course_id = ?';
        $params = [$student_id, $course_id];
        $result = $this->crud->readAllRows($query, $params, 'ii');

        if ($result->num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }









    //---------StudentDAsh 
    public function getStudentCourse($student_id)
    {
        // SQL query to fetch courses that the student is enrolled in 
        $sql = 'SELECT c.* 
                FROM courses c
                JOIN student_courses sc ON c.course_id = sc.course_id
                WHERE  sc.student_id = ?';


        return $this->crud->readAllRows($sql, [$student_id], 'i');
    }
}
