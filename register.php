<?php
require_once 'config.php';

$error = '';
$success = '';

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
)";
$conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = 'admin'; // Set role as admin by default

    // Validation
    if (strlen($username) < 4) {
        $error = 'Username must be at least 4 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            // Check if username or email already exists
            $stmt = prepare_query("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username or email already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = prepare_query(
                    "INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)",
                    [$username, $email, $hashed_password, $full_name, $phone, $role]
                );

                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now login.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Registration Error: " . $e->getMessage());
        }
    }
}

// Check if there are any admin users
$stmt = prepare_query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
$stmt->execute();
$result = $stmt->get_result();
$admin_exists = $result->fetch_assoc()['admin_count'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Shan-e-Madina Petroleum Services</title>
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
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(0, 0, 0, 0.3));
            z-index: -1;
        }
        .register-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(5px);
        }
        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: none;
        }
        .card-header {
            background: linear-gradient(120deg, #1e88e5, #0d47a1);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            border: none;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="white" stroke-width="0.5" fill="none" opacity="0.2"/></svg>') center/50px 50px repeat;
            opacity: 0.1;
        }
        .card-header h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(215, 193, 193, 0.2);
        }
        .card-body {
            padding: 2.5rem;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a237e;
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
        }
        .form-text {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .btn-register {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
            color: white;
            text-transform: uppercase;
            font-weight: 600;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #0d47a1, #1e88e5);
            transform: translateY(-2px);
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #2c3e50;
        }
        .login-link a {
            color: #1a237e;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .login-link a:hover {
            color: #0d47a1;
            text-decoration: underline;
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
            color: #27ae60;
        }
        .alert-link {
            font-weight: 500;
            text-decoration: none;
        }
        .alert-link:hover {
            text-decoration: underline;
        }
        .logo-container {
            margin-bottom: 1.5rem;
        }
        .pso-logo {
            width: 100px;
            height: auto;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.2));
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <div class="logo-container">
                    <img src="Images/PakistanStateOilLogo.png" alt="PSO Logo" class="pso-logo">
                </div>
                <h3>Shan-e-Madina Petroleum Services</h3>
                <p class="mb-0">Create New Account</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <br>
                        <a href="login.php" class="alert-link">Click here to login</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <div class="form-text">Minimum 4 characters</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-id-card me-2"></i>Full Name
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-2"></i>Phone Number
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <input type="hidden" name="role" value="admin">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="8" pattern=".{8,}"
                                   title="Password must be at least 8 characters long">
                            <div class="form-text">Minimum 8 characters</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required minlength="8"
                                   pattern=".{8,}" title="Password must be at least 8 characters long">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 