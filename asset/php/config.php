<?php
// Session handling - must be at the very top
if (session_status() === PHP_SESSION_NONE) {
    $session_path = '/';
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => $session_path,
        // 'domain' => parse_url('https://plankton-app-us3aj.ondigitalocean.app', PHP_URL_HOST),
        'domain' => parse_url('http://194.163.171.218/lms', PHP_URL_HOST),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600);

    session_start();
}

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Database Configuration
define('DB_HOST', '152.42.223.81');
define('DB_NAME', 'lms');
define('DB_USER', 'root');
define('DB_PASS', '');
// define('DB_PASS', 'Black@123');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);
// define('APP_URL', 'https://plankton-app-us3aj.ondigitalocean.app');
define('APP_URL', 'http://194.163.171.218/lms');

// Application Settings
define('APP_NAME', 'EduPortal');
define('ADMIN_EMAIL', 'admin@your-domain.com');

// File Upload Settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024);  // 10MB in bytes
define('ALLOWED_FILE_TYPES', [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png'
]);
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// Email Configuration (if needed)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-password');
define('SMTP_FROM', 'noreply@your-domain.com');

// Security
define('HASH_COST', 12);

// Time Zone
date_default_timezone_set('UTC');

// Helper Functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}


function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}


function redirect($path) {
    $url = APP_URL;
    if (!empty($path)) {
        $url .= '/' . ltrim($path, '/');
    }
    header("Location: " . $url);
    exit;
}

function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function error_response($message, $status = 400) {
    json_response(['error' => $message], $status);
}

function regenerate_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}