<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assignment.php';

$assignment = new Assignment();

if (isset($_GET['submission_id'])) {
    $submission_id = $_GET['submission_id'];
    $submission = $assignment->getSubmissionData($submission_id);
}

// If submission exists, download it
if (isset($submission) && $submission) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($submission['file_name']) . '"');
    header('Content-Length: ' . strlen($submission['file_data']));
    echo $submission['file_data'];
    exit;
}

// If submission doesn't exist
die("Error occurred. Failed to fetch submission.");
?>