<?php
require_once 'config.php';

// Clear any existing session data at login page
if (!isset($_POST['username'])) {
    session_unset();
    session_destroy();
    session_start();
}

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard.php');
        exit();
    } else {
        header('Location: user_dashboard.php');
        exit();
    }
}

$error = '';
$debug_info = '';  // For development only

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            // Get user from database
            $stmt = prepare_query("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1", [$username]);
            
            if ($stmt === false) {
                throw new Exception("Database query preparation failed");
            }

            if (!$stmt->execute()) {
                throw new Exception("Query execution failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            
            if ($result === false) {
                throw new Exception("Failed to get result set");
            }

            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Clear any existing session data
                session_unset();
                session_destroy();
                session_start();
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // All users should be redirected to dashboard.php
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password';
                $debug_info = 'User not found or password mismatch';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Login Error: " . $e->getMessage());
            $debug_info = "Exception: " . $e->getMessage();
        }
    }
}

// Add debug information to the page (remove in production)
if (!empty($debug_info)) {
    error_log("Login Debug: " . $debug_info);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shan-e-Madina Petroleum Services</title>
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="Images/PakistanStateOilLogo.png" alt="PSO Logo" class="pso-logo">
                </div>
                <h3>Shan-e-Madina Petroleum Services</h3>
                <p class="mb-0">Login to your account</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required minlength="8" 
                               pattern=".{8,}"
                               title="Password must be at least 8 characters long">
                        <label for="password">Password (min. 8 characters)</label>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>

                <div class="forgot-links">
                    <a href="forgot_username.php"><i class="fas fa-user me-1"></i>Forgot Username?</a>
                    <a href="forgot_password.php"><i class="fas fa-lock me-1"></i>Forgot Password?</a>
                </div>

                <div class="register-link">
                    New User? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.needs-validation').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    </script>
</body>
</html> 