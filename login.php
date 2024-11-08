<?php

require_once '../database.php';
require_once 'User.php';


$user = new Users();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Authenticate user
    $userId = $user->login($username, $password, $role); // Store the return value

    if ($userId) {
        // Start session and store user information
        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['user_id'] = $userId; // Store the user ID in session

        // Redirect based on the role
        if ($role === 'teacher') {
            header('Location: ../Teacher Dash/index.php');
            exit();
        } elseif ($role === 'student') {
            header('Location: student_dashboard.php');
            exit();
        }
    } else {
        $error_msg = "Login failed. Incorrect username or password.";
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <title>Login</title>
</head>

<body>
    <div class="container">
        <div class="border-form">
            <h3>Log In</h3>
            <div class="form">
                <form action="login.php" method="POST">
                    <label for="">Username: <input type="text" name="username" required></label>
                    <label for="">Password: <input type="password" name="password" required></label>
                    <label for="">Role:</label>
                    <select name="role" required>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                    <input type="submit" value="Log In">
                </form>
                <p><a href="register.php">Don't have an account yet?</a></p>
                <?php if (!empty($error_msg)) {
                    echo $error_msg;
                } ?>
            </div>
        </div>

        <div class="title">
            <h1>Learning Management System</h1>
        </div>
    </div>
</body>

</html>