<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';  
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assignment.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Create an instance of the Assignment class
$assignment = new Assignment();

// Check if necessary POST data is set
if (isset($_POST['assignment_id']) && isset($_POST['course_id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $assignment_id = $_POST['assignment_id'];
    $course_id = $_POST['course_id'];

    try {
        // Call the deleteAssignment function to delete the assignment from the database
        $assignment->deleteAssignment($assignment_id);
        
        // Redirect back to the course page after successful deletion
        $_SESSION['message'] = "Assignment successfully deleted!";
        header("Location: create_assignment.php?course_id=" . $course_id);
        exit;
    } catch (Exception $e) {
        echo "Error deleting assignment: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
