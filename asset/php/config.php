<?php
// Start of config.php - Session Configuration FIRST
$session_path = '/';
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => $session_path,
    'domain' => parse_url('https://plankton-app-us3aj.ondigitalocean.app', PHP_URL_HOST),
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Database Configuration
define('DB_HOST', 'db-mysql-nyc3-14016-do-user-17700770-0.d.db.ondigitalocean.com');
define('DB_NAME', 'defaultdb');
define('DB_USER', 'doadmin');
define('DB_PASS', 'AVNS_l3SW8eljPIvmmGNUCFK');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 25060);
define('PHP_SELF', 'https://plankton-app-us3aj.ondigitalocean.app');

// Application Settings
define('APP_NAME', 'EduPortal');
define('APP_URL', 'https://plankton-app-us3aj.ondigitalocean.app');
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
define('HASH_COST', 12); // For password hashing

// Time Zone
date_default_timezone_set('UTC');

// Helper Functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length));
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

// Response Functions
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function error_response($message, $status = 400) {
    json_response(['error' => $message], $status);
}

// Session Security
function regenerate_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
} 