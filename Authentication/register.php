<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once 'Classes/User.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Checks if the request method is POST
    $Registration = new Users();

    // Retrieves user input from the form
    $first_name = $_POST['firstname'];
    $last_name = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Call the registration method with the collected data
    $message = $Registration->registerinfo($first_name, $last_name, $email, $username, $password, $role);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Registration</title>
    <link rel="stylesheet" href="register.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Main container -->
<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <!-- Registration container -->
    <div class="row border rounded-5 p-4 bg-white shadow-lg" style="max-width: 900px; width: 100%;">

        <!-- Left box with image -->
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <img src="https://i.pinimg.com/736x/81/b0/0c/81b00c19bfb961bf1d7000e850262cac.jpg" class="img-fluid" style="max-width: 300px;" alt="Registration Image">
        </div>

        <!-- Right box with registration form -->
        <div class="col-md-6">

            <!-- Title -->
            <div class="center-text">
                <h2>CLASS REGISTRATION</h2>
            </div>

            <!-- Registration form -->
            <form action="register.php" method="POST">
                <!-- First Name input -->
                <div class="form-group">
                    <input type="text" name="firstname" id="firstname" class="form-control form-control-lg" placeholder="First Name" required>
                </div>

                <!-- Last Name input -->
                <div class="form-group">
                    <input type="text" name="lastname" id="lastname" class="form-control form-control-lg" placeholder="Last Name" required>
                </div>

                <!-- Email input -->
                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="Email" required>
                </div>

                <!-- Username input -->
                <div class="form-group">
                    <input type="text" name="username" id="username" class="form-control form-control-lg" placeholder="Username" required>
                </div>

                <!-- Password input -->
                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Password" required>
                </div>

                <!-- Role selection -->
                <div class="form-group">
                    <select name="role" id="role" class="form-control form-control-lg" style="font-size:17px;" required>
                        <option value="Student">Student</option>
                        <option value="Teacher">Teacher</option>
                    </select>
                </div>

                <!-- Submit button -->
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Register</button>
                </div>
            </form>

            <!-- Display messages (if any) -->
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'error') !== false ? 'error' : (strpos($message, 'warning') !== false ? 'warning' : 'success'); ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Links to login -->
            <div class="text-center">
                <p>Already have an account? <a href="login.php">Log in here</a></p>
            </div>

        </div>

    </div>

</div>

</body>
</html>
