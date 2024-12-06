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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="forgotPass.css">
    <title>Reset Password</title>
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-lg" style="max-width: 500px; width: 100%;">
            <h1 class="text-center mb-4">Reset Password</h1>
            <p class="text-center text-muted">A code has been sent to your email account!</p>
            <form action="resetPass.php" method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="reset_code">Reset Code:</label>
                    <input type="text" name="reset_code" id="reset_code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Reset</button>
            </form>
            <p class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </p>
            <?php if (!empty($message)) : ?>
            <p class="text-danger text-center mt-3"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        </div>
    </div>
</body>

</html>