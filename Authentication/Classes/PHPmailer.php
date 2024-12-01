<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

class Mailer {
    private $database;
    private $conn;

    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }

    public function checkEmailExists($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
    
        return $result->num_rows > 0; // Returns true if the email exists
    }
    
    public function saveResetCode($email, $resetCode) {
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // Code expires in 1 hour
        $stmt = $this->conn->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $resetCode, $expiry, $email);
        return $stmt->execute(); // Returns true if successful
    }
    
    public function verifyResetCode($email, $resetCode) {
        $stmt = $this->conn->prepare("SELECT reset_expiry FROM users WHERE email = ? AND reset_code = ?");
        $stmt->bind_param("ss", $email, $resetCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    
        // Check if code exists and is within the expiry time
        if ($user && strtotime($user['reset_expiry']) > time()) {
            return true;
        } else {
            return false;
        }
    }
    
    public function updatePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        return $stmt->execute(); // Returns true if successful
    }
    
    public function sendResetCode($email, $resetCode) {
        $mail = new PHPMailer(true);
    
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Gmail SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'liamkatigbak0@gmail.com';  // account ko
            $mail->Password = 'pmdo yhzj llpe mbwu';  // app password created din sa acct ko
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS encryption
            $mail->Port = 587;  // SMTP port for TLS
    
            // Email details
            $mail->setFrom('liamkatigbak0@gmail.com', 'LMS-Reset Code');  // Sender email 
            $mail->addAddress($email);  // Recipient email/email from user
            
            $mail->isHTML(true);  // Send as HTML
            $mail->Subject = 'Reset Password';  // Subject of the email
            $mail->Body    = "<p>This is your code for password reset: <strong>$resetCode</strong></p>";  // Email body
    
            // Send the email
            $mail->send();
            return true;  // Return true if mail was sent successfully
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);  // Log the error
            return false;  // Return false if there was an error
        }
    }
}

?>