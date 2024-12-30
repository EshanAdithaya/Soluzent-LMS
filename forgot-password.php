<!-- forgot-password.php -->
<?php
session_start();
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';
require_once 'EmailSender.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $response['message'] = 'Please enter your email address';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                
                // Store token in database
                $stmt = $pdo->prepare('INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
                $stmt->execute([$user['id'], $token]);
                
                // Send email
                $emailSender = new EmailSender();
                if ($emailSender->sendPasswordResetEmail($email, $token)) {
                    $response = [
                        'success' => true,
                        'message' => 'Password reset instructions have been sent to your email.'
                    ];
                } else {
                    $response['message'] = 'Failed to send reset email. Please try again later.';
                }
            } else {
                // For security, don't reveal if email exists
                $response = [
                    'success' => true,
                    'message' => 'If an account exists with this email, you will receive password reset instructions.'
                ];
            }
        } catch (Exception $e) {
            error_log('Password Reset Error: ' . $e->getMessage());
            $response['message'] = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Education Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php include_once 'navbar.php';?>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Forgot Password</h2>
                <p class="mt-2 text-sm text-gray-600">Enter your email to reset your password</p>
            </div>

            <?php if (!empty($response['message'])): ?>
            <div class="bg-<?php echo $response['success'] ? 'green' : 'red'; ?>-100 border border-<?php echo $response['success'] ? 'green' : 'red'; ?>-400 text-<?php echo $response['success'] ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($response['message']); ?></span>
            </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" name="email" type="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
</body>
</html>