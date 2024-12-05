<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'teacher')) {
    echo '<script>alert("Please login to access the dashboard."); window.location.href = "../login.php";</script>';
    exit;
}


?>