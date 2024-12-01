<?php
// includes/config.php

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'education_platform');
define('DB_USER', 'root');         // Change in production
define('DB_PASS', '');             // Change in production
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'EduPortal');
define('APP_URL', 'http://localhost/education-platform'); // Change in production
define('ADMIN_EMAIL', 'admin@example.com');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);   // Set to 1 in production (HTTPS)
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // Session timeout in seconds (1 hour)

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
define('SMTP_FROM', 'noreply@example.com');

// Security
define('HASH_COST', 12); // For password hashing

// Time Zone
date_default_timezone_set('UTC');

// Create database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

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
    header("Location: " . APP_URL . "/" . $path);
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

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}