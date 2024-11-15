<?php

class Database{
    private $host = 'localhost';
    private $db_name = 'lms';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

        //check if may error
        if($this->conn->connect_error){
            die('Error Connection' .$this->conn->connect_error);
        }
    }

    public function getConnection(){
        return $this->conn;
    }

}

?>