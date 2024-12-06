<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../../../Classes/Material.php';


$material = new Material();
$material_id = $_GET['id'];
$file = $material->getLearningMaterialData($material_id);

if ($file) {
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
    header('Content-Length: ' . strlen($file['file_data']));
    
    // Output file data
    echo $file['file_data'];
    exit;
} else {
    die("File not found");
}