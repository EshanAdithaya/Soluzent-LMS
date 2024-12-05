<?php
// First check if user is logged in at all
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Then check if user has appropriate role
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}
?>