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
    
    //teacher dash
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

    //method for student fetching assessment
    public function getAssessmentByCourse($course_id){
        $query = 'SELECT * FROM assessments WHERE course_id = ? ORDER BY due_date ASC';
        $params = [$course_id];
        $result = $this->crud->readAllRows($query, $params, 'i');
        return $result;
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
        $sql = "SELECT option_text FROM question_options WHERE question_id = ? AND is_correct = 1 LIMIT 1;";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $answer = $result->fetch_assoc(); // Using fetch_assoc() for the correct answer
        return $answer ? $answer['option_text'] : null; // Return the option_text if found, otherwise null
    }


    
    public function getAnswerIdentification($question_id) {
        // Update query to select correct_answer column
        $sql = "SELECT correct_answer FROM question_answers WHERE question_id = ?";
        
        // Prepare and execute the statement
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch the result
        $answer = $result->fetch_assoc(); // Using fetch_assoc() to get associative array
        
        // Return the correct_answer if found, otherwise return null
        return $answer ? $answer['correct_answer'] : null; 
    }
    
    public function startAssessmentAttempt($assessment_id, $student_id) {
        $query = "INSERT INTO assessment_attempts (assessment_id, student_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $assessment_id, $student_id);
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function getAssessmentQuestions($assessment_id) {
        $query = "SELECT * FROM assessment_questions WHERE assessment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $questions = [];
        while ($row = $result->fetch_assoc()) { // Use fetch_assoc to get each row as an associative array
            $questions[] = $row;
        }
        return $questions;
    }
    
 // Submit student responses and grade the attempt
 public function submitAssessmentAttempt($attempt_id, $student_responses, $questions) {
    // Loop through the student responses and insert into the student_responses table
    foreach ($student_responses as $response) {
        // Ensure correct types and default values for NULL
        $question_id = $response['question_id'];
        $selected_option_id = $response['selected_option_id'] ?? NULL;
        $text_response = $response['text_response'] ?? NULL;
        $is_correct = $response['is_correct'] ?? NULL;
        $points_earned = $response['points_earned'] ?? 0;

        // Handle NULL values gracefully in bind_param
        $query = "INSERT INTO student_responses (attempt_id, question_id, selected_option_id, text_response, is_correct, points_earned) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        // Make sure the variable types match the placeholders in the SQL query
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters to match the SQL query
        $stmt->bind_param("iiisii", $attempt_id, $question_id, $selected_option_id, $text_response, $is_correct, $points_earned);
        
        $stmt->execute();
    }
}


// Update the status of the assessment attempt
public function updateAssessmentAttemptStatus($attempt_id, $status) {
    $query = "UPDATE assessment_attempts SET status = ? WHERE attempt_id = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("si", $status, $attempt_id);
    $stmt->execute();
}

// Update the total score after grading is done
public function updateAttemptTotalScore($attempt_id, $total_score) {
    $query = "UPDATE assessment_attempts SET score = ? WHERE attempt_id = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("di", $total_score, $attempt_id);
    $stmt->execute();

    }
    
    public function getAssessmentOptions($question_id) {
        $query = "SELECT * FROM question_options WHERE question_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC); // Return options as an associative array
    }
   
    // Inside your Assessment class
public function getTotalPoints($assessment_id) {
    // Assuming you have a questions table with points
    $query = "SELECT SUM(points) AS total_points FROM assessment_questions WHERE assessment_id = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $stmt->bind_result($total_points);
    $stmt->fetch();
    $stmt->close();

    return $total_points;
}


}
?>
