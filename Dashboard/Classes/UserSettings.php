<?php
require_once 'crud.php';

class UserSettings
{
    private $database;
    private $conn;
    private $crud;

    public function __construct()
    {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->crud = new Crud($this->conn);
    }

    public function getUserData($user_id)
    {
        $query = 'SELECT * FROM users WHERE user_id = ?';
        $params = [$user_id];
        return $this->crud->readSingleRow($query, $params, 'i');
    }

    public function updateDetails($first_name, $last_name, $username, $password = null, $user_id)
    {
        // Update name and username
        $query = 'UPDATE users SET first_name = ?, last_name = ?, username = ? WHERE user_id = ?';
        $params = [$first_name, $last_name, $username, $user_id];
        return $this->crud->updateRecord($query, $params, 'sssi');

        // Update password if provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = 'UPDATE users SET password = ? WHERE user_id = ?';
            $params = [$hashed_password, $user_id];
            return $this->crud->updateRecord($query, $params, 'si');
        }
    }

    public function deleteAccount($user_id)
    {
        $query = 'DELETE FROM users WHERE user_id = ?';
        $params = [$user_id];
        return $this->crud->deleteRecord($query, $params, 'i');
    }
}
