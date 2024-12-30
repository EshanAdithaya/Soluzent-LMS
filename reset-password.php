<!-- reset-password.php -->
<?php
session_start();
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';

$response = ['success' => false, 'message' => ''];
$validToken = false;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    try {
        // Verify token and check if it's not expired
        $stmt = $pdo->prepare('
            SELECT rt.user_id, u.email 
            FROM reset_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ');
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();
        
        if ($tokenData) {
            $validToken = true;
        }
    } catch (Exception $e) {
        error_log('Token Verification Error: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $response['message'] = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters long';
    } else {
        try {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hashedPassword, $tokenData['user_id']]);
            
            // Delete used token
            $stmt = $pdo->prepare('DELETE FROM reset_tokens WHERE token = ?');
            $stmt->execute([$token]);
            
            $response = [
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ];
            
            // Redirect to login after 3 seconds
            header("refresh:3;url=login.php");
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
    <title>Reset Password - Education Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php include_once 'navbar.php';?>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Reset Password</h2>
                <p class="mt-2 text-sm text-gray-600">Enter your new password</p>
            </div>

            <?php if (!$validToken): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">Invalid or expired reset link. Please request a new password reset.</span>
            </div>
            <?php else: ?>
                <?php if (!empty($response['message'])): ?>
                <div class="bg-<?php echo $response['success'] ? 'green' : 'red'; ?>-100 border border-<?php echo $response['success'] ? 'green' : 'red'; ?>-400 text-<?php echo $response['success'] ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($response['message']); ?></span>
                </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6" method="POST" action="">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input id="password" name="password" type="password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once 'footer.php'; ?>
</body>
</html>
