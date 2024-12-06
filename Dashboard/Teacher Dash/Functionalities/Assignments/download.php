<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assignment.php';

$assignment = new Assignment();

if (isset($_GET['assignment_id'])) {
    $assignment_id = $_GET['assignment_id'];
    $assignments = $assignment->getAssignmentData($assignment_id);  // Define this method for assignments
}

// If assignment exists, download it
if (isset($assignments) && $assignments) {
    // Assignment download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($assignments['file_name']) . '"');
    header('Content-Length: ' . strlen($assignments['file_data']));
    echo $assignments['file_data'];  // Make sure to access the correct data
    exit;
}

// If neither material nor assignment exists
die("Error occurred. Failed to fetch data.");
?>
