<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';  
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assessment.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$assessment = new Assessment();

// Check if necessary POST data is set
if (isset($_POST['assessment_id']) && isset($_POST['course_id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $assessment_id = $_POST['assessment_id'];
    $course_id = $_POST['course_id'];

    try {
        // Call the deleteAssignment function to delete the assessment from the database
        $assessment->deleteAssessment($assessment_id);
        
        // Redirect back to the course page after successful deletion
        header("Location: create_assessment.php?course_id=" . $course_id);
        exit;
    } catch (Exception $e) {
        echo "Error deleting assessment: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}

