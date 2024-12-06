<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../../../Classes/Assessment.php';

// Verify teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /FinalProj/login.php");
    exit();
}

// Check if required data is present
if (!isset($_POST['submission_id']) || !isset($_POST['grade'])) {
    die("Missing required data");
}

$submission_id = $_POST['submission_id'];
$grade = floatval($_POST['grade']);
$feedback = isset($_POST['feedback']) ? $_POST['feedback'] : '';

// Add this line to get assignment_id from POST
$assignment_id = isset($_POST['assignment_id']) ? $_POST['assignment_id'] : null;

if (!$assignment_id) {
    die("Missing assignment ID");
}

$assignment = new Assignment();
$result = $assignment->gradeSubmission($submission_id, $grade, $feedback);

if ($result) {
    header("Location: view_submission.php?assignment_id=" . $assignment_id);
} else {
    die("Error updating grade");
}