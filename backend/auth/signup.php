<?php
// auth/signup.php

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
    $password = $data['password'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $response['message'] = 'Please fill in all fields';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Email already exists';
            echo json_encode($response);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare('
            INSERT INTO users (name, email, phone, password, role, created_at) 
            VALUES (?, ?, ?, ?, "student", NOW())
        ');

        $stmt->execute([$name, $email, $phone, $hashedPassword]);

        $response = [
            'success' => true,
            'message' => 'Account created successfully'
        ];

    } catch (PDOException $e) {
        $response['message'] = 'Database error occurred';
        // Log the error: error_log($e->getMessage());
    }
}

echo json_encode($response);