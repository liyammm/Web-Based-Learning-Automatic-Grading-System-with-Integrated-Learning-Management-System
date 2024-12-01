<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php'; // Database connection
require_once 'Classes/PHPmailer.php';

$mail = new Mailer(); 
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    if ($mail->checkEmailExists($email)) {
        $resetCode = mt_rand(100000, 999999); // Generate a 6-digit reset code
        $mail->saveResetCode($email, $resetCode); // Save the reset code and expiry

        if ($mail->sendResetCode($email, $resetCode)) {
            header('Location: resetPass.php');
        } else {
            $message = "Failed to send email. Please try again later.";
        }
    } else {
        $message = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="forgotPass.css">
    <title>Forgot Password</title>
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <form action="forgotPass.php" method="POST">
            <label for="email">Enter your email:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Send Reset Code</button>
        </form>
        <p class="message"><?php echo $message; ?></p>
        <a href="resetPass.php">Already have a code? Reset Password</a>
    </div>
</body>
</html>

