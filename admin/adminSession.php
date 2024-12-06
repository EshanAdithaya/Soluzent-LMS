<?php
// adminSession.php
require_once '../asset/php/config.php'; // This will handle session start

if (!isset($_SESSION['user_id'])) {
    error_log('Session not found: ' . print_r($_SESSION, true));
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    header("Location: " . APP_URL . "/login.php");
    exit;
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: " . APP_URL . "/login.php");
    exit;
}