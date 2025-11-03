<?php
/**
 * Database Configuration File
 * Petrol Pump Management System
 */

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for development (you may want to disable this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');          // Database username
define('DB_PASS', '');              // Database password
define('DB_NAME', 'Petrol_pump_system'); // Database name

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

} catch (Exception $e) {
    // Log error (in a production environment, you should log to a file instead)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show user-friendly message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

/**
 * Helper function to execute queries safely
 * @param string $sql The SQL query
 * @param array $params Array of parameters to bind
 * @return mysqli_stmt|false Returns prepared statement or false on failure
 */
function prepare_query($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt && !empty($params)) {
        $types = str_repeat('s', count($params)); // Assume all strings, modify if needed
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt;
}

/**
 * Helper function to close database connection
 */
function close_connection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('close_connection'); 