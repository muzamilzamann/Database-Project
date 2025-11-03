<?php
require_once 'config.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if email exists in database
            $stmt = prepare_query("SELECT username FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
            
            if ($stmt && $stmt->execute()) {
                $result = $stmt->get_result();
                if ($user = $result->fetch_assoc()) {
                    // Create a new PHPMailer instance
                    $mail = new PHPMailer(true);

                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'YOUR_GMAIL@gmail.com'; // Replace with your Gmail
                        $mail->Password = 'YOUR_APP_PASSWORD'; // Replace with your app password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('YOUR_GMAIL@gmail.com', 'Your Name'); // Replace with your details
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Username Recovery';
                        $mail->Body = "Hello,<br><br>"
                                  . "You have requested to recover your username.<br><br>"
                                  . "Your username is: <strong>{$user['username']}</strong><br><br>"
                                  . "If you did not request this information, please ignore this email.<br><br>"
                                  . "Best regards,<br>Your Website Team";
                        $mail->AltBody = "Hello,\n\n"
                                     . "You have requested to recover your username.\n\n"
                                     . "Your username is: {$user['username']}\n\n"
                                     . "If you did not request this information, please ignore this email.\n\n"
                                     . "Best regards,\nYour Website Team";

                        $mail->send();
                        $success = 'Your username has been sent to your email address';
                    } catch (Exception $e) {
                        $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    // Don't reveal if email exists or not
                    $success = 'If your email exists in our system, you will receive your username';
                }
            } else {
                $error = 'An error occurred. Please try again later';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Username</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('Images/pso.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            width: 100%;
            color: var(--primary-color); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .success {
            color: #28a745;
            margin-bottom: 15px;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-user"></i> Forgot Username</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Username
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html> 