<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="forgotPass.css">
    <title>Forgot Password</title>

</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-lg" style="max-width: 500px; width: 100%;">
            <div class="text-center">
                <img src="https://img.freepik.com/premium-vector/girl-overworked-with-studies-schoolgirl-with-headache-migraine-negative-feelings-emotions-depression-frustration-flat-vector-illustration_118813-28487.jpg?w=360"
                    alt="Overworked Girl" class="img-fluid mb-3" style="max-height: 200px;">
            </div>
            <h1 class="text-center mb-4">Forgot Password</h1>
            <form action="forgotPass.php" method="POST">
                <div class="form-group">
                    <label for="email">Enter your email:</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Code</button>
            </form>
            <p class="text-center mt-3">
                <a href="resetPass.php">Already have a code? Reset Password</a>
            </p>
            <p class="text-center mt-3">
                <a href="login.php">Back to Login</a>
                <?php if (!empty($message)) : ?>
            <p class="text-danger text-center mt-3"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        </div>
    </div>
</body>

</html>