<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Material.php';

$material = new Material();

// Check if material ID is provided and fetch the respective data
if (isset($_GET['material_id'])) {
    $material_id = $_GET['material_id'];
    $materials = $material->getLearningMaterialData($material_id); // Assuming this method fetches the file data and name
}

// If material exists, download it
if (isset($materials) && $materials) {
    // Learning Material download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($materials['file_name']) . '"');
    header('Content-Length: ' . strlen($materials['file_data']));
    echo $materials['file_data'];
    exit;
} else {
    die("Error occurred. Failed to fetch material data.");
}
?>
