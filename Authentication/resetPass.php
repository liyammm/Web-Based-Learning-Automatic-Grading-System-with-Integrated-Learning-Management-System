<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once 'Classes/PHPMailer.php';

$mail = new Mailer();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $resetCode = $_POST['reset_code'];
    $newPassword = $_POST['new_password'];

    if ($mail->verifyResetCode($email, $resetCode)) {
        if ($mail->updatePassword($email, $newPassword)) {
            $message = "Password updated successfully.";
            header('Location: login.php');
            exit; // Stop execution after redirection
        } else {
            $message = "Failed to update password. Please try again later.";
        }
    } else {
        $message = "Invalid reset code or code has expired.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="forgotPass.css">
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <h5>A code has been sent to your email account!</h5>
        <form action="resetPass.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
            <label for="reset_code">Reset Code:</label>
            <input type="text" name="reset_code" id="reset_code" required>
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>
            <button type="submit">Reset</button>
        </form>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    </div>
</body>
</html>
 
