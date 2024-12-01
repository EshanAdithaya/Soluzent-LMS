<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Education Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <!-- Header -->
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Create your account</h2>
                <p class="mt-2 text-sm text-gray-600">Join our learning platform</p>
            </div>

            <!-- Signup Form -->
            <form class="mt-8 space-y-6" id="signupForm">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input id="name" name="name" type="text" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input id="phone" name="phone" type="tel" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <button type="submit" id="submitBtn"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('signupForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            
            // Get form values
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Validation
            if (!name || !email || !phone || !password || !confirmPassword) {
                alert('Please fill in all fields');
                return;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }

            try {
                submitBtn.textContent = 'Creating Account...';
                submitBtn.disabled = true;

                const response = await fetch('auth/signup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name,
                        email,
                        phone,
                        password
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    alert('Account created successfully! Please login.');
                    window.location.href = 'login.php';
                } else {
                    alert(data.message || 'Error creating account');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                submitBtn.textContent = 'Create Account';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>


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