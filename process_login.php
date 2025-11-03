<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $response['message'] = 'Please enter both username and password';
    } else {
        try {
            // Get user from database
            $stmt = prepare_query("SELECT * FROM users WHERE username = ? AND status = 'active'", [$username]);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Set success response
                $response['success'] = true;
                $response['redirect'] = $user['role'] === 'admin' ? 'dashboard.php' : 'user_dashboard.php';
            } else {
                $response['message'] = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $response['message'] = 'An error occurred. Please try again later.';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}

echo json_encode($response); 