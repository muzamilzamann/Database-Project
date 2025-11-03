<?php
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard based on role
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit();
}

// If not logged in, redirect to login page
header('Location: login.php');
exit();
?> 