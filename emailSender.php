<?php
require 'emailSender/Exception.php';
require 'emailSender/OAuth.php';
require 'emailSender/POP3.php';
require 'emailSender/SMTP.php';
require 'emailSender/PHPMailer.php';
class EmailSender {
    private $mailer;
    
    public function __construct() {
         // Make sure you have PHPMailer installed via composer
        
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings for Gmail SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'testfeeldbroken10@gmail.com'; // Your Gmail address
        $this->mailer->Password = 'suul aqjj ltju klrn'; // Your Gmail App Password
        $this->mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        
        // Default sender
        $this->mailer->setFrom('testfeeldbroken10@gmail.com', 'SOLUZENT LMS');
    }
    
    public function sendPasswordResetEmail($to, $token) {
        try {
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset Request';
            
            // Create HTML message
            $resetLink = "https://yourdomain.com/reset-password.php?token=" . $token;
            $this->mailer->Body = "
                <h2>Password Reset Request</h2>
                <p>You have requested to reset your password. Click the link below to proceed:</p>
                <p><a href='{$resetLink}'>Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
            ";
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Add other email sending methods as needed
    public function sendGenericEmail($to, $subject, $body) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return false;
        }
    }
}