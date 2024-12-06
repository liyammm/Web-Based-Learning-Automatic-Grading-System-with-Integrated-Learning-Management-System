<?php
require_once 'crud.php';

class CourseSettings
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

    // Delete a course
    public function deleteCourse($course_id)
    {
        return $this->crud->deleteRecord(
            'DELETE FROM courses WHERE course_id = ?',
            [$course_id],
            'i'
        );
    }

    // Update course details: name and weighted averages
    public function updateCourse($course_id, $course_name, $assignment_weight, $quiz_weight, $exam_weight)
    {
        return $this->crud->updateRecord(
            'UPDATE courses SET course_name = ?, assignment_weight = ?, quiz_weight = ?, exam_weight = ? WHERE course_id = ?',
            [$course_name, $assignment_weight, $quiz_weight, $exam_weight, $course_id],
            'sdddi'
        );
    }

    // Get course details
    public function getCourseDetails($course_id)
    {
        return $this->crud->readSingleRow(
            'SELECT * FROM courses WHERE course_id = ?',
            [$course_id],
            'i'
        );
    }

}
