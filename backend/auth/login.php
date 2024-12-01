<?php
// auth/login.php

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];

    if (empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all fields';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            $response = [
                'success' => true,
                'role' => $user['role']
            ];
        } else {
            $response['message'] = 'Invalid email or password';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error occurred';
        // Log the error: error_log($e->getMessage());
    }
}

echo json_encode($response);