<?php
// Error Reporting
require 'asset/php/config.php';
require 'asset/php/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Database Configuration
define('DB_HOST', 'db-mysql-nyc3-14016-do-user-17700770-0.d.db.ondigitalocean.com');
define('DB_NAME', 'defaultdb');
define('DB_USER', 'doadmin');
define('DB_PASS', 'AVNS_l3SW8eljPIvmmGNUCFK');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 25060);

// Start Session
session_start();

// Database Connection
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];

    // Handle SSL Certificate
    $ssl_ca = __DIR__ . '/ca-certificate.crt';
    if (!file_exists($ssl_ca)) {
        $cert = file_get_contents('https://dl.cacerts.digicert.com/DigiCertGlobalRootCA.crt.pem');
        if ($cert) {
            file_put_contents($ssl_ca, $cert);
        }
    }
    
    if (file_exists($ssl_ca)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
    }

    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $response['message'] = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters long';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $response['message'] = 'Email already exists';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user with explicit role as student
                $stmt = $pdo->prepare('
                    INSERT INTO users (name, email, phone, password, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ');

                $stmt->execute([$name, $email, $phone, $hashedPassword, 'student']);

                if ($stmt->rowCount() > 0) {
                    $response = [
                        'success' => true,
                        'message' => 'Account created successfully! Please login.'
                    ];
                    
                    // Redirect to login page on success
                    header('Location: login.php');
                    exit;
                } else {
                    throw new Exception('Failed to insert user');
                }
            }
        } catch (Exception $e) {
            $response['message'] = 'Database error occurred: ' . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Education Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo htmlspecialchars($baseUrl ?? ''); ?>asset/js/devtools-prevention.js"></script>
</head>
<?php include_once 'navbar.php';?>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <!-- Header -->
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Create your account</h2>
                <p class="mt-2 text-sm text-gray-600">Join our learning platform</p>
            </div>

            <?php if (!empty($response['message'])): ?>
            <div class="bg-<?php echo $response['success'] ? 'green' : 'red'; ?>-100 border border-<?php echo $response['success'] ? 'green' : 'red'; ?>-400 text-<?php echo $response['success'] ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($response['message']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Signup Form -->
            <form class="mt-8 space-y-6" method="POST" action="" id="signupForm">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input id="name" name="name" type="text" required 
                            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required 
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input id="phone" name="phone" type="tel" required 
                            value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
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
                        <span id="buttonText">Create Account</span>
                        <svg id="loadingIcon" class="animate-spin h-5 w-5 ml-2 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
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
    <?php include_once 'footer.php'; ?>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const buttonText = document.getElementById('buttonText');
            const loadingIcon = document.getElementById('loadingIcon');

            // Show loading state
            buttonText.textContent = 'Creating Account...';
            loadingIcon.classList.remove('hidden');
            submitBtn.disabled = true;
        });

        // Handle form submission on Enter key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('signupForm').submit();
            }
        });
    </script>
</body>
</html>