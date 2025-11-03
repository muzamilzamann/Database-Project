<?php
require_once 'config.php';

$error = '';
$success = '';

// Local SMTP settings
ini_set('SMTP', 'localhost');
ini_set('smtp_port', '25');
ini_set('sendmail_from', 'admin@localhost');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if email exists in database
            $stmt = prepare_query("SELECT id, username FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
            
            if ($stmt && $stmt->execute()) {
                $result = $stmt->get_result();
                if ($user = $result->fetch_assoc()) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Save token in database
                    $update = prepare_query(
                        "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?",
                        [$token, $expiry, $user['id']]
                    );
                    
                    if ($update && $update->execute()) {
                        // Send reset email
                        $reset_link = "http://{$_SERVER['HTTP_HOST']}/reset_password.php?token=" . $token;
                        $to = $email;
                        $subject = "Password Reset Request";
                        $message = "Hello {$user['username']},\n\n";
                        $message .= "You have requested to reset your password. Click the link below to reset it:\n\n";
                        $message .= $reset_link . "\n\n";
                        $message .= "This link will expire in 1 hour.\n\n";
                        $message .= "If you did not request this reset, please ignore this email.\n\n";
                        $message .= "Best regards,\nYour Website Team";
                        
                        $headers = "From: admin@localhost\r\n";
                        $headers .= "Reply-To: admin@localhost\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion();
                        
                        if (mail($to, $subject, $message, $headers)) {
                            $success = 'Password reset instructions have been sent to your email';
                        } else {
                            $error = 'Failed to send reset email. Please try again later';
                        }
                    } else {
                        $error = 'An error occurred. Please try again later';
                    }
                } else {
                    // Don't reveal if email exists or not
                    $success = 'If your email exists in our system, you will receive password reset instructions';
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
    <title>Forgot Password</title>
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
        <h2><i class="fas fa-lock"></i> Forgot Password</h2>
        
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
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html> 