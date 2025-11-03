<?php
// Include database configuration
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

// Security Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Authentication Functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Formatting Functions
function format_currency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function format_date($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function format_datetime($datetime, $format = 'd M Y, h:i A') {
    return date($format, strtotime($datetime));
}

// Alert Functions
function set_alert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_alert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function display_alert() {
    $alert = get_alert();
    if ($alert) {
        $type = $alert['type'];
        $message = $alert['message'];
        $icon = '';
        
        switch ($type) {
            case 'success':
                $icon = 'check-circle';
                break;
            case 'danger':
                $icon = 'exclamation-circle';
                break;
            case 'warning':
                $icon = 'exclamation-triangle';
                break;
            case 'info':
                $icon = 'info-circle';
                break;
        }
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                <i class='fas fa-{$icon} me-2'></i>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

// Pagination Function
function generate_pagination($total_records, $records_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_records / $records_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
    $prev_page = $current_page - 1;
    $pagination .= "<li class='page-item {$prev_disabled}'>
                     <a class='page-link' href='" . sprintf($url_pattern, $prev_page) . "' aria-label='Previous'>
                       <span aria-hidden='true'>&laquo;</span>
                     </a>
                   </li>";
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= "<li class='page-item {$active}'>
                         <a class='page-link' href='" . sprintf($url_pattern, $i) . "'>{$i}</a>
                       </li>";
    }
    
    // Next button
    $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
    $next_page = $current_page + 1;
    $pagination .= "<li class='page-item {$next_disabled}'>
                     <a class='page-link' href='" . sprintf($url_pattern, $next_page) . "' aria-label='Next'>
                       <span aria-hidden='true'>&raquo;</span>
                     </a>
                   </li>";
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

// File Upload Function
function handle_file_upload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }
    
    $new_filename = uniqid() . '.' . $extension;
    $upload_path = 'uploads/' . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $upload_path
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

// Activity Logging Function
function log_activity($user_id, $action, $details, $pdo) {
    $data = [
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return insert_record('activity_logs', $data, $pdo);
}
?> 