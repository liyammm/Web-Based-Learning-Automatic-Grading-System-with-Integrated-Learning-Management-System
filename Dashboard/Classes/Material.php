<?php

require_once 'crud.php';

class Material {
    private $database;
    private $conn;
    private $crud;

    public function __construct(){
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    // LEARNING MATERIALS SECTION

    // Upload learning material
    public function uploadLearningMaterial($course_id, $file_name, $description, $file_data)
    {
        $query = 'INSERT INTO learning_materials (course_id, file_name, description, file_data) 
              VALUES (?, ?, ?, ?)';
        $params = [$course_id, $file_name, $description, $file_data];
        return $this->crud->createRecord($query, $params, 'isss');
    }

    // Get learning materials for a course
    public function getLearningMaterials($course_id)
    {
        $query = 'SELECT learning_materials_id, file_name, description, upload_date 
              FROM learning_materials WHERE course_id = ?';
        $params = [$course_id];
        return $this->crud->readAllRows($query, $params, 'i');
    }

    // Get file data for downloading
    public function getLearningMaterialData($material_id)
    {
        $query = 'SELECT file_name, file_data 
              FROM learning_materials WHERE learning_materials_id = ?';
        $params = [$material_id];
        return $this->crud->readSingleRow($query, $params, 'i');
    }

    // Delete learning material
    public function deleteLearningMaterial($material_id)
    {
        $query = 'DELETE FROM learning_materials WHERE learning_materials_id = ?';
        $params = [$material_id];
        return $this->crud->deleteRecord($query, $params, 'i');
    }









    //for student functions/methods
    public function getLearningMaterialsByCourse($course_id){
        $query = 'SELECT learning_materials_id, course_id, file_name, description, upload_date FROM learning_materials WHERE course_id = ?';
        $params = [$course_id];
        $result = $this->crud->readAllRows($query, $params, 'i');
        return $result;
    }
}


?>