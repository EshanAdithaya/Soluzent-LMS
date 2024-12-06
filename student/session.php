<?php
// First check if user is logged in at all
if (!isset($_SESSION['user_id'])) {
    error_log('No user session found');
    header("Location: " . APP_URL . "/login.php");
    exit;
}

// Then check if role exists and is valid
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'student'])) {
    error_log('Invalid or missing role: ' . ($_SESSION['role'] ?? 'none'));
    header("Location: " . APP_URL . "/login.php");
    exit;
}

// At this point, we have a valid logged-in user with a valid role
?>