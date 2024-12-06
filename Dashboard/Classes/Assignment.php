<?php
require_once 'crud.php';

class Assignment
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

    //ASSIGNMENT SECTION
    //adding assignment
    public function createAssignment($course_id, $assignment_name, $assignment_type, $description, $due_date, $total_points)
    {
        if (empty($due_date)) {
            throw new Exception("Due date is required");
        }

        // Format due_date
        $formatted_due_date = str_replace('T', ' ', $due_date) . ':00';
        
        $query = 'INSERT INTO assignments 
                  (course_id, assignment_name, assignment_type, description, due_date, total_points) 
                  VALUES (?, ?, ?, ?, ?, ?)';
                  
        $params = [
            $course_id, 
            $assignment_name, 
            $assignment_type, 
            $description, 
            $formatted_due_date,
            $total_points
        ];
        
        return $this->crud->createRecord($query, $params, 'issssd');
    }

    public function fetchAssignment($course_id)
    {
        $query = "SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function updateAssignment($assignment_id, $assignment_name, $description, $due_date, $total_points)
    {
        if (empty($due_date)) {
            throw new Exception("Due date is required");
        }

        return $this->crud->updateRecord(
            'UPDATE assignments SET assignment_name = ?, description = ?, due_date = ?, total_points = ? WHERE assignment_id = ?',
            [$assignment_name, $description, $due_date, $total_points, $assignment_id],
            'sssdi'
        );
    }

    public function deleteAssignment($assignment_id)
    {
        return $this->crud->deleteRecord(
            'DELETE FROM assignments WHERE assignment_id = ?',
            [$assignment_id],
            'i'
        );
    }

    // Get file data for downloading assignment
    public function getAssignmentData($assignment_id)
    {
        return $this->crud->readSingleRow(
            'SELECT assignment_id, assignment_name, description, due_date, file_name, file_data, total_points
             FROM assignments 
             WHERE assignment_id = ?',
            [$assignment_id],
            'i'
        );
    }

    public function getAssignmentById($assignment_id) {
        $stmt = $this->conn->prepare("SELECT * FROM assignments WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    //method for student fetching assignment
    public function getAssignmentByCourse($course_id){
        $query = 'SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC';
        $params = [$course_id];
        $result = $this->crud->readAllRows($query, $params, 'i');
        return $result;
    }

    public function hasSubmission($assignment_id, $student_id) {
        $query = "SELECT * FROM assignment_submissions 
                  WHERE assignment_id = ? AND student_id = ?";
        $params = [$assignment_id, $student_id];
        $result = $this->crud->readSingleRow($query, $params, 'ii');
        return $result ? true : false;
    }

    public function getSubmissionDetails($assignment_id, $student_id) {
        $query = "SELECT * FROM assignment_submissions 
                  WHERE assignment_id = ? AND student_id = ?";
        $params = [$assignment_id, $student_id];
        $result = $this->crud->readSingleRow($query, $params, 'ii');
        return $result;
    }

    public function submitAssignment($assignment_id, $student_id, $submission_text, $file_name, $file_data) {
        $query = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_name, file_data) VALUES (?, ?, ?, ?, ?)";
        $params = [$assignment_id, $student_id, $submission_text, $file_name, $file_data];
        return $this->crud->createRecord($query, $params, 'issss');
    }

    public function getAllSubmissions($assignment_id) {
        $query = "SELECT s.*, 
                  CONCAT(u.first_name, ' ', u.last_name) as student_name 
                  FROM assignment_submissions s 
                  JOIN users u ON s.student_id = u.user_id 
                  WHERE s.assignment_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getAssignmentDetails($assignment_id) {
        $sql = "SELECT * FROM assignments WHERE assignment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // for grade submission
    public function gradeSubmission($submission_id, $grade, $feedback) {
        
        $stmt = $this->conn->prepare("UPDATE assignment_submissions 
                               SET grade = ?, feedback = ?
                               WHERE submission_id = ?");
        
        $stmt->bind_param("dsi", $grade, $feedback, $submission_id);
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    public function getStudentSubmission($assignment_id, $student_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM assignment_submissions 
            WHERE assignment_id = ? AND student_id = ?
            ORDER BY submission_date DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $assignment_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    public function updateSubmission($assignment_id, $student_id, $submission_text, $file_name = null, $file_data = null) {
        $sql = "UPDATE assignment_submissions 
                SET submission_text = ?, 
                    submission_date = NOW()";
        
        // Add file update only if new file is provided
        if ($file_name !== null && $file_data !== null) {
            $sql .= ", file_name = ?, file_data = ?";
        }
        
        $sql .= " WHERE assignment_id = ? AND student_id = ?";
        
        if ($file_name !== null && $file_data !== null) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssii", $submission_text, $file_name, $file_data, $assignment_id, $student_id);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sii", $submission_text, $assignment_id, $student_id);
        }
        
        return $stmt->execute();
    }
    
    public function hasExistingSubmission($assignment_id, $student_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM assignment_submissions 
                                     WHERE assignment_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $assignment_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }


    //for downloading submission
    public function getSubmissionData($submission_id)
    {
        return $this->crud->readSingleRow(
            'SELECT submission_id, file_name, file_data 
             FROM assignment_submissions
             WHERE submission_id = ?',
            [$submission_id],
            'i'
        );
    }

}
