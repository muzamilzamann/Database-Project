<?php
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit();
}

// Verify token and check if it's expired
$stmt = prepare_query(
    "SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() AND status = 'active' LIMIT 1",
    [$token]
);

if (!$stmt || !$stmt->execute()) {
    header('Location: login.php');
    exit();
}

$result = $stmt->get_result();
if (!$result || !$result->fetch_assoc()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $update_stmt = prepare_query(
                "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL 
                 WHERE reset_token = ? AND reset_token_expiry > NOW()",
                [$hashed_password, $token]
            );
            
            if ($update_stmt && $update_stmt->execute() && $update_stmt->affected_rows > 0) {
                $success = 'Your password has been reset successfully. You can now login with your new password.';
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Password Reset Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Shan-e-Madina Petroleum Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        body {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 0;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('Images/pso.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            filter: blur(1px);
            transform: scale(1.1);
            z-index: -1;
        }
        .reset-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }
        .reset-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .reset-header {
            background: linear-gradient(120deg, #1a237e, #0d47a1);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
        }
        .reset-header h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .reset-body {
            padding: 2.5rem 2rem;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
        }
        .form-floating {
            margin-bottom: 1.5rem;
        }
        .form-floating > .form-control {
            border-radius: 12px;
            border: 2px solid rgba(0,0,0,0.1);
            padding: 1rem 1rem;
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }
        .btn-reset {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent-color), #2196f3);
            border: none;
            color: white;
            font-weight: 500;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .alert {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-to-login a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: -1rem;
            margin-bottom: 1rem;
            padding-left: 1rem;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h3>Reset Password</h3>
                <p class="mb-0">Enter your new password</p>
            </div>
            <div class="reset-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="New Password" required minlength="8">
                        <label for="password">New Password</label>
                    </div>
                    <div class="password-requirements">
                        <i class="fas fa-info-circle me-1"></i>Password must be at least 8 characters long
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm Password" required minlength="8">
                        <label for="confirm_password">Confirm Password</label>
                    </div>
                    <button type="submit" class="btn btn-reset">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>
                </form>
                <?php endif; ?>

                <div class="back-to-login">
                    <a href="login.php"><i class="fas fa-arrow-left me-2"></i>Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 