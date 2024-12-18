<?php
session_start();
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';


// Add this at the top with your other constants
define('RECAPTCHA_SITE_KEY', '6LfS-pMqAAAAABIZAGYVYyCZf2UwDbtehvEsYYti');
define('RECAPTCHA_SECRET_KEY', '6LfS-pMqAAAAABSp6DEft8G35rthZ6UeTlqSbbO1');

// Initialize variables
$activeSession = false;
$currentUser = '';
$response = ['success' => false, 'message' => ''];

// First check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $stmt = $pdo->prepare('
            SELECT u.id, u.name, u.role 
            FROM users u 
            JOIN remember_tokens rt ON u.id = rt.user_id 
            WHERE rt.token = ? AND rt.expires > NOW()
        ');
        $stmt->execute([$_COOKIE['remember_token']]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Update last access
            $updateStmt = $pdo->prepare('UPDATE users SET last_access = NOW() WHERE id = ?');
            $updateStmt->execute([$user['id']]);

            // Redirect to appropriate dashboard
            if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: student/Dashboard.php');
            }
            exit;
        }
    } catch (PDOException $e) {
        error_log('Remember Token Error: ' . $e->getMessage());
    }
}

// Then check for existing session
if (isset($_SESSION['user_id'])) {
    $activeSession = true;
    $currentUser = $_SESSION['name'];
    
    // Handle logout request
    if (isset($_POST['logout'])) {
        // Remove remember token from database and cookie
        if (isset($_COOKIE['remember_token'])) {
            try {
                $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE token = ?');
                $stmt->execute([$_COOKIE['remember_token']]);
                setcookie('remember_token', '', time() - 3600, '/');
            } catch (PDOException $e) {
                error_log('Logout Error: ' . $e->getMessage());
            }
        }
        
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // If session exists and no logout request, redirect to appropriate dashboard
    if ($_SERVER['REQUEST_URI'] === '/login.php') {
        header('Location: ' . ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher' ? 'admin/dashboard.php' : 'student/dashboard.php'));
        exit;
    }
}

// Finally process login attempt if no session exists
if (!$activeSession && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all fields';
    // } elseif (empty($recaptchaResponse)) {
    //     $response['message'] = 'Please complete the reCAPTCHA';
    // } 
    }else {
        // Verify reCAPTCHA
        $recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response=" . $recaptchaResponse);
        $recaptchaData = json_decode($recaptchaVerify);

        // if (!$recaptchaData->success) {
        //     $response['message'] = 'reCAPTCHA verification failed';
        // } else {
            try {
                $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    
                    // Update last access time
                    $updateStmt = $pdo->prepare('UPDATE users SET last_access = NOW() WHERE id = ?');
                    $updateStmt->execute([$user['id']]);
                    
                    // Handle remember me functionality
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Store remember me token in database
                        $stmt = $pdo->prepare('INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)');
                        $stmt->execute([$user['id'], $token, $expires]);
                        
                        // Set cookie
                        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                    }

                    // Immediate redirect based on role
                    if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: student/dashboard.php');
                    }
                    exit;
                } else {
                    $response['message'] = 'Invalid email or password';
                }
            } catch (PDOException $e) {
                error_log('Login Error: ' . $e->getMessage());
                $response['message'] = 'Database error occurred. Please try again later.';
            }
        }
    }
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Education Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add reCAPTCHA script in head section -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- <script src="<?php 
    // echo htmlspecialchars($baseUrl ?? ''); 
    ?>asset/js/devtools-prevention.js"></script> -->

</head>
<?php include_once 'navbar.php';?>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <?php if ($activeSession): ?>
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Active Session</h2>
                <p class="mt-4 text-md text-gray-600">
                    You are currently logged in as <strong><?php echo htmlspecialchars($currentUser); ?></strong>.<br>
                    You need to logout first to access a different account.
                </p>
                <form method="POST" class="mt-6">
                    <button type="submit" name="logout"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Logout
                    </button>
                </form>
            </div>
            <?php else: ?>
            <!-- Existing login form content -->
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Welcome back</h2>
                <p class="mt-2 text-sm text-gray-600">Please sign in to your account</p>
            </div>

            <?php if (!empty($response['message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($response['message']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="mt-8 space-y-6" method="POST" action="" id="loginForm">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required 
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">Remember me</label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <!-- Add reCAPTCHA widget before submit button -->
                <div class="flex justify-center">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                </div>

                <div>
                    <button type="submit" id="submitBtn"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span id="buttonText">Sign in</span>
                        <svg id="loadingIcon" class="hidden animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <a href="signup.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign up
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once 'footer.php'; ?>
    <!-- Modify your script section at the bottom of login.php -->
<script>
function showLoadingState() {
    const button = document.getElementById('submitBtn');
    const buttonText = document.getElementById('buttonText');
    const loadingIcon = document.getElementById('loadingIcon');
    
    // Check if reCAPTCHA is completed
    // if (grecaptcha.getResponse() === '') {
    //     alert('Please complete the reCAPTCHA');
    //     return false;
    // }
    
    // Disable button and show loading state
    button.disabled = true;
    button.classList.add('opacity-75', 'cursor-not-allowed');
    buttonText.textContent = 'Signing in...';
    loadingIcon.classList.remove('hidden');
    return true;
}

// Only add event listeners if we're on the login form (not active session)
<?php if (!$activeSession): ?>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        if (!showLoadingState()) {
            e.preventDefault();
        }
    });

    // Add event listeners for Enter key press
    document.getElementById('email').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            showLoadingState();
            document.getElementById('loginForm').submit();
        }
    });

    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            showLoadingState();
            document.getElementById('loginForm').submit();
        }
    });
<?php endif; ?>
</script>
</body>
</html>