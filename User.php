<?php

require_once '../database.php';

class Users{
    private $database;
    private $conn;

    public function __construct() {
        $this->database = new Database(); //instance of database
        $this->conn= $this->database->getConnection(); //gets the connection
    }

    public function registerinfo($first_name,$last_name,$email,$username,$password,$role){
        $message='';
    
        $plcholder = $this->conn->prepare("SELECT * FROM users WHERE username = ? OR email =?" );//checks if may parehas na username or email ka na ininput
        $plcholder -> bind_param("ss", $username, $email); // s for string
        $plcholder->execute();
        $result = $plcholder->get_result();
    
        if($result ->num_rows > 0){ //checks how many users were found in the database
            $message = '<div class = "message warning" role = "alert">User already exists.</div>';
    
        }else {
                $hashpas = password_hash($password, PASSWORD_DEFAULT);
    
                // inserts new user into the database
    
                $plcholder = $this->conn->prepare("INSERT INTO users(first_name,last_name,email,username,password,role) VALUES(?,?,?,?,?,?)");
                $plcholder->bind_param("ssssss", $first_name,$last_name,$email,$username,$hashpas,$role);
    
    
                if ($plcholder->execute()){// checks if the statement was successfuly executed in the database
                    header('Location: login.php');
                } else{
                    $message='<div class = "message error" role = "alert"> Error' .$plcholder->error .'</div>';
                }
            }
    
            $plcholder->close();
            return $message;
    
    }
        
    public function __destruct(){
        $this->conn->close();
    }


    
    public function login($username, $password, $role){
        // Prepare the SQL statement to get user ID along with password
        $stmt = $this->conn->prepare("SELECT user_id, password FROM users WHERE username = ? AND role = ?");
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Bind the result to fetch the user ID and the hashed password
            $stmt->bind_result($userId, $hashedPassword);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashedPassword)) {
                return $userId; 
            }
        }

        return false; // Authentication failed
    }

}


?>
