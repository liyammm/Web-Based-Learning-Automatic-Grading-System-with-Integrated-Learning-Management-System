<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';

class Crud
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function readSingleRow($query, $params, $paramTypes)
    {
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function readAllRows($query, $params, $paramTypes)
    {
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function createRecord($query, $params, $paramTypes)
    {
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        return $stmt->execute();
    }

    public function updateRecord($query, $params, $paramTypes){
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        return $stmt->execute();
    }

    public function deleteRecord($query, $params, $paramTypes)
    {
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        return $stmt->execute();
    }

}

    
?>



















