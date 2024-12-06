<?php

require_once '../db.php';
require_once 'Classes/User.php';

$user = new Users();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Authenticate user
    $userId = $user->login($username, $password, $role);

    if ($userId) {
        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['user_id'] = $userId;

        if ($role === 'teacher') {
            header('Location: ../Dashboard/Teacher Dash/index.php');
            exit();
        } elseif ($role === 'student') {
            header('Location: ../Dashboard/Student Dash/index.php');
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"> 
    <title>Login</title>
</head>

<body>

<!-- Main container -->
<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <!-- Login container -->
    <div class="row border rounded-5 p-4 bg-white shadow-lg" style="max-width: 900px; width: 100%;">

        <!-- Left box -->
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <img src="https://i.pinimg.com/736x/81/b0/0c/81b00c19bfb961bf1d7000e850262cac.jpg" class="img-fluid" style="max-width: 300px;" alt="Featured Image">
        </div>

        <!-- Right box with the login-->
        <div class="col-md-6">

        
            <div class="center-text">
                <h2>LOGIN</h2>
            </div>

            <form action="login.php" method="POST">

                <div class="form-group">
                    <input type="text" name="username" id="username" class="form-control form-control-lg" placeholder="Email or Username" required>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Password" required>
                </div>

                <div class="form-group">
                    <select name="role" id="role" class="form-control form-control-lg "style="font-size:17px;" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Log In</button>
                </div>
            </form>

            <!-- Forgot password and sign up links -->
            <div class="text-center">
                <p><a href="forgotPass.php">Forgot Password?</a></p>
                <p>Don't have an account yet? <a href="register.php">Sign Up</a></p>
            </div>

            <!-- Display error message -->
            <?php if (!empty($error_msg)): ?>
                <p class="text-danger text-center mt-3"><?php echo $error_msg; ?></p>
            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
