<?php

require_once 'asset/php/config.php';
require_once 'asset/php/db.php';
require_once 'emailSender.php';

function handlePasswordReset($email, $pdo) {
    error_log("Received password reset request for email: $email");
    $response = ['success' => false, 'message' => ''];

    if (!is_valid_email($email)) {
        $response['message'] = 'Please enter a valid email address';
        return $response;
    }

    try {
        // Set the timezone to Sri Lanka
        date_default_timezone_set('Asia/Colombo');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = generate_random_string(32);
            
            // Calculate expiration time
            $expiresAt = (new DateTime())->add(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

            // Insert the reset token with the calculated expiration time
            $stmt = $pdo->prepare('INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expiresAt]);

            $resetUrl = APP_URL . '/reset-password.php?token=' . $token;
            $emailSender = new EmailSender();
            if ($emailSender->sendPasswordResetEmail($email, $resetUrl)) {
                $response = [
                    'success' => true,
                    'message' => 'Password reset instructions have been sent to your email.'
                ];
            } else {
                $response['message'] = 'Failed to send reset email. Please try again later.';
            }
        } else {
            $response = [
                'success' => true,
                'message' => 'If an account exists with this email, you will receive password reset instructions.'
            ];
        }
    } catch (Exception $e) {
        error_log('Password Reset Error: ' . $e->getMessage()); // Log the error message
        $response['message'] = 'An error occurred: ' . $e->getMessage(); // Provide more detail for debugging
    }

    return $response;
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $response = ['success' => false, 'message' => 'Please enter your email address'];
    } else {
        $response = handlePasswordReset($email, $pdo);
    }
    
    // Use the helper function from config to check if it's an AJAX request
    if (is_ajax_request()) {
        json_response($response);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'navbar.php'; ?>
    
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Forgot Password</h2>
                <p class="mt-2 text-sm text-gray-600">Enter your email to reset your password</p>
            </div>

            <div id="message-container" class="hidden">
                <div id="message" class="px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"></span>
                </div>
            </div>

            <form id="reset-form" class="mt-8 space-y-6" method="POST">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" name="email" type="email" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Send Reset Link
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Remember your password? 
                    <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reset-form');
            const messageContainer = document.getElementById('message-container');
            const messageElement = document.getElementById('message');

            if (form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    // Show loading state
                    const submitButton = form.querySelector('button[type="submit"]');
                    const originalButtonText = submitButton.innerHTML;
                    submitButton.innerHTML = 'Sending...';
                    submitButton.disabled = true;

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        messageContainer.classList.remove('hidden');
                        messageElement.className = `px-4 py-3 rounded relative ${data.success ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'}`;
                        messageElement.querySelector('span').textContent = data.message;
                        
                        if (data.success) {
                            form.reset();
                        }
                    })
                    .catch(error => {
                        messageContainer.classList.remove('hidden');
                        messageElement.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
                        messageElement.querySelector('span').textContent = 'An error occurred. Please try again later.';
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        // Restore button state
                        submitButton.innerHTML = originalButtonText;
                        submitButton.disabled = false;
                    });
                });
            }
        });
    </script>
</body>
</html>