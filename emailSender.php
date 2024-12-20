<?php
// include("env.php"); // Assuming this file contains your environment variables.
require "emailSender/SMTP.php";
require "emailSender/PHPMailer.php";
require "emailSender/Exception.php";
require "asset/php/config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Input data
$name = trim($_POST["name"]);
$email = trim($_POST["email"]);
$subject = trim($_POST["subject"]);
$message = trim($_POST["message"]);


// if (intval($responseKeys["success"]) !== 1) {
//     echo 'Please complete the reCAPTCHA correctly.';
// } else {

    // Validation
    if (empty($name)) {
        echo "Enter Name";
    } elseif (empty($email)) {
        echo "Enter Email";
    } elseif (strlen($email) > 100) {
        echo "Email Address Should Contain Less Than 100 Characters";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid Email";
    } elseif (empty($subject)) {
        echo "Enter Subject";
    } elseif (empty($message)) {
        echo "Enter Message";
    } else {
        // Send email
        

        try {
            $mail = new PHPMailer;
            // $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->isSMTP(); 
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'skaushalya708@gmail.com';
            // Fetch the password securely from the environment variable
            $mail->Password = 'ingk ndva cjdv ywlu'; 
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            
            $mail->setFrom($email, $name);
            $mail->addReplyTo($email, $name);
            $mail->addAddress('skaushalya708@gmail.com');
            $mail->isHTML(true);
            $mail->Subject = 'New Email - ' . $subject;
            
            $bodyContent = '<p>Name: ' . htmlspecialchars($name) . '</p>';
            $bodyContent .= '<p>Email: ' . htmlspecialchars($email) . '</p>';
            $bodyContent .= '<p>Subject: ' . htmlspecialchars($subject) . '</p>';
            $bodyContent .= '<p>Message: ' . nl2br(htmlspecialchars($message)) . '</p>'; // Converts newlines to <br>
            
            $mail->Body = $bodyContent;

            if (!$mail->send()) {
                echo 'Message Send Failed: ' . $mail->ErrorInfo;
            } else {
                echo 'Thank you for your message. We will get back to you soon!';
            }
        } catch (Exception $e) {
            echo 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
    }
// }
?>
