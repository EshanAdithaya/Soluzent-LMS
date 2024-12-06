<?php
// adminSession.php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Access denied. Please log in first.');</script>";
    echo "<script>window.location.href = '" . APP_URL . "/login.php';</script>";
    header("Location: " . APP_URL . "/login.php");
    error_log('Session not found: ' . print_r($_SESSION, true));
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    
    exit;
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    echo "<script>alert('Access denied. Insufficient privileges.');</script>";
    echo "<script>window.location.href = '" . APP_URL . "/login.php';</script>";
    header("Location: " . APP_URL . "/login.php");
    exit;
}
?>

