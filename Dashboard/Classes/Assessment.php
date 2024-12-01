<?php
require_once 'crud.php';

class Assessment {
    private $database;
    private $conn;
    private $crud;
    private $assessmentId;

    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    public function createAssessment($course_id, $assessment_name, $assessment_type, $description, $due_date, $duration_minutes, $total_points) {
        $query = 'INSERT INTO assessments (course_id, assessment_name, assessment_type, description, due_date, duration_minutes, total_points) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)';
        $params = [$course_id, $assessment_name, $assessment_type, $description, $due_date, $duration_minutes, $total_points];
        return $this->crud->createRecord($query, $params, 'ssssdis');
    }
    
    public function fetchAssessment($course_id) {
        $query = "SELECT * FROM assessments WHERE course_id = ? ORDER BY due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch all results as an associative array
        $assessments = [];
        while ($row = $result->fetch_assoc()) {
            $assessments[] = $row;
        }
        return $assessments;  // Return an array of assessments
    }

    // Method to get assessment details by assessment_id
    public function getAssessmentDetails($assessment_id) {
        $query = 'SELECT * FROM assessments WHERE assessment_id = ?';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $assessment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the result as an associative array
        $assessment = $result->fetch_assoc();
        return $assessment; // Return the assessment details
    }

    public function updateAssessment($assessment_id, $assessment_name, $description, $due_date, $total_points) {
        if (empty($due_date)) {
            throw new Exception("Due date is required");
        }

        return $this->crud->updateRecord(
            'UPDATE assessments SET assessment_name = ?, description = ?, due_date = ?, total_points = ? WHERE assessment_id = ?',
            [$assessment_name, $description, $due_date, $total_points, $assessment_id],
            'sssdi'
        );
    }

    public function deleteAssessment($assessment_id) {
        return $this->crud->deleteRecord(
            'DELETE FROM assessments WHERE assessment_id = ?',
            [$assessment_id],
            'i'
        );
    }
    
    public function addQuestion($assessment_id, $question_text, $question_type, $points, $question_order) {
        $query = 'INSERT INTO assessment_questions (assessment_id, question_text, question_type, points, question_order) 
                  VALUES (?, ?, ?, ?, ?)';
        $params = [$assessment_id, $question_text, $question_type, $points, $question_order];
        
        $this->crud->createRecord($query, $params, 'issdi');
        
        // Return the last inserted question_id
        return $this->conn->insert_id;
    }
    
    
    public function addOptions($question_id, $option_text, $is_correct, $option_order) {
        $query = 'INSERT INTO question_options (question_id, option_text, is_correct, option_order) 
                  VALUES (?, ?, ?, ?)'; 
        $params = [$question_id, $option_text, $is_correct, $option_order];
        return $this->crud->createRecord($query, $params, 'isii');
    }
    

    public function addAnswer($question_id, $correct_answer) {
        $query = 'INSERT INTO question_answers (question_id, correct_answer) 
                  VALUES (?, ?)';
        $params = [$question_id, $correct_answer];
        return $this->crud->createRecord($query, $params, 'is');
    }

    public function fetchQuestions($assessment_id){

        $sql = "SELECT * FROM assessment_questions WHERE assessment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $questions = [];
        while ($row = $result->fetch_array()) { // Using fetch_array() to get both indexes
            $questions[] = $row; // Storing the result in a numeric array
        }
        return $questions;

    } public function fetchOption($question_id){
        $sql = "SELECT * FROM question_options WHERE question_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $options = [];
        while ($row = $result->fetch_array()) { // Using fetch_array() to get both indexes
            $options[] = $row; // Storing the result in a numeric array
        }
        return $options;

    }

    public function getAnswer($question_id) {
        $sql = "SELECT question_answer FROM identification_answers WHERE question_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $answer = $result->fetch_array(); // Using fetch_array() for correct answer
        return $answer[0]; // Fetching the first column (answer) using numerical index
    }


}
?>
