<?php
// Start session securely
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_only_cookies' => true
]);

require_once __DIR__ . '/asset/php/config.php';
require_once __DIR__ . '/asset/php/db.php';
require_once 'student/session.php';

// Enable error reporting with logging
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Utility function for date formatting
function formatDate($date, $format = 'F j, Y') {
    return $date ? date($format, strtotime($date)) : 'Never';
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$messages = [];
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_profile':
                    // Validate inputs
                    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
                    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                    $phone = filter_var(trim($_POST['phone']), FILTER_SANITIZE_STRING);

                    if (empty($name) || empty($email) || empty($phone)) {
                        throw new Exception('All fields are required');
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Invalid email format');
                    }

                    if (!preg_match('/^[+]?[\d\s-]{10,}$/', $phone)) {
                        throw new Exception('Invalid phone number format');
                    }

                    // Check email uniqueness
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                    $stmt->execute([$email, $userId]);
                    if ($stmt->rowCount() > 0) {
                        throw new Exception('Email is already in use');
                    }

                    // Update user information - explicitly NOT updating role
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, last_access = NOW() WHERE id = ?');
                    $stmt->execute([$name, $email, $phone, $userId]);
                    
                    $messages['success'][] = 'Profile updated successfully';
                    break;

                case 'update_password':
                    $currentPassword = $_POST['current_password'];
                    $newPassword = $_POST['new_password'];
                    $confirmPassword = $_POST['confirm_password'];

                    // Validate password requirements
                    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                        throw new Exception('All password fields are required');
                    }

                    if ($newPassword !== $confirmPassword) {
                        throw new Exception('New passwords do not match');
                    }

                    if (strlen($newPassword) < 8) {
                        throw new Exception('Password must be at least 8 characters long');
                    }

                    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
                        throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, and one number');
                    }

                    // Verify current password
                    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!password_verify($currentPassword, $user['password'])) {
                        throw new Exception('Current password is incorrect');
                    }

                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $stmt->execute([$hashedPassword, $userId]);

                    $messages['success'][] = 'Password updated successfully';
                    
                    // Log the password change
                    error_log("Password changed for user ID: $userId at " . date('Y-m-d H:i:s'));
                    break;
            }
            
            $pdo->commit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $messages['error'][] = $e->getMessage();
        error_log("Error in profile update: " . $e->getMessage());
    }
}

// Fetch user data with activity statistics
try {
    $stmt = $pdo->prepare('
        SELECT 
            u.name, 
            u.email, 
            u.phone, 
            u.role, 
            u.created_at, 
            u.last_access,
            COUNT(DISTINCT e.class_id) as enrolled_classes
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        WHERE u.id = ?
        GROUP BY u.id
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log("User not found: $userId");
        die('User not found');
    }

    // Fetch recent activity if student
    $recentActivity = [];
    if ($user['role'] === 'student') {
        $stmt = $pdo->prepare('
            SELECT 
                c.name as class_name,
                m.title as material_title,
                e.enrolled_at
            FROM enrollments e
            JOIN classes c ON e.class_id = c.id
            LEFT JOIN materials m ON c.id = m.class_id
            WHERE e.student_id = ?
            ORDER BY e.enrolled_at DESC
            LIMIT 5
        ');
        $stmt->execute([$userId]);
        $recentActivity = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('Error loading user data');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($user['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- <script src="asset/js/devtools-prevention.js"></script> -->
</head>
<body class="bg-gray-50">
    <!-- Replace header include based on role -->
    <?php
    if ($user['role'] === 'admin' && $user['role'] === 'admin' ) {
        include_once 'admin/admin-header.php';
    } else {
        include_once 'student/student-header.php';
    }
    ?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="text-gray-500 mb-8">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="<?php echo $user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/Dashboard.php'; ?>" class="hover:text-gray-700">
                        Dashboard
                    </a>
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                        <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                </li>
                <li>
                    <span class="text-gray-700">My Profile</span>
                </li>
            </ol>
        </nav>

        <!-- Messages -->
        <?php foreach (['success' => 'green', 'error' => 'red'] as $type => $color): ?>
            <?php if (!empty($messages[$type])): ?>
                <?php foreach ($messages[$type] as $message): ?>
                    <div class="mb-4 bg-<?php echo $color; ?>-100 border border-<?php echo $color; ?>-400 text-<?php echo $color; ?>-700 px-4 py-3 rounded relative">
                        <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer close-message">
                            <svg class="fill-current h-6 w-6 text-<?php echo $color; ?>-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <!-- Profile Header -->
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <div class="flex items-center">
                    <div class="h-16 w-16 bg-gray-200 rounded-full flex items-center justify-center text-2xl font-bold text-gray-600">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </h1>
                        <p class="text-sm text-gray-500">
                            <?php echo ucfirst(htmlspecialchars($user['role'])); ?> Account
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex">
                    <a href="?tab=profile" class="<?php echo $activeTab === 'profile' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        <i class="fas fa-user mr-2"></i> Profile Information
                    </a>
                    <a href="?tab=security" class="<?php echo $activeTab === 'security' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        <i class="fas fa-lock mr-2"></i> Security
                    </a>
                    <a href="?tab=activity" class="<?php echo $activeTab === 'activity' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        <i class="fas fa-history mr-2"></i> Activity
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <?php if ($activeTab === 'profile'): ?>
                    <!-- Profile Information Form -->
                    <form method="POST" class="space-y-6" id="profileForm">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" name="name" id="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="email" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone text-gray-400"></i>
                                    </div>
                                    <input type="tel" name="phone" id="phone" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>" required
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <!-- Read-only role display -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Account Type</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-id-badge text-gray-400"></i>
                                    </div>
                                    <input type="text" readonly
                                           value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>"
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>

                <?php elseif ($activeTab === 'security'): ?>
                    <!-- Security Settings -->
                    <form method="POST" class="space-y-6" id="passwordForm">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Password must be at least 8 characters long and contain:
                                        <ul class="list-disc ml-5 mt-1">
                                            <li>At least one uppercase letter</li>
                                            <li>At least one lowercase letter</li>
                                            <li>At least one number</li>
                                        </ul>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="current_password" id="current_password" required
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                    <input type="password" name="new_password" id="new_password" required
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-check-double text-gray-400"></i>
                                    </div>
                                    <input type="password" name="confirm_password" id="confirm_password" required
                                           class="pl-10 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-key mr-2"></i> Update Password
                            </button>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- Activity Tab -->
                    <div class="space-y-6">
                        <!-- Activity Stats -->
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-calendar text-gray-400 text-2xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Member Since</dt>
                                                <dd class="text-lg font-medium text-gray-900">
                                                    <?php echo formatDate($user['created_at']); ?>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-clock text-gray-400 text-2xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Last Access</dt>
                                                <dd class="text-lg font-medium text-gray-900">
                                                    <?php echo formatDate($user['last_access'], 'F j, Y g:i A'); ?>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($user['role'] === 'student'): ?>
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-book text-gray-400 text-2xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Classes</dt>
                                                <dd class="text-lg font-medium text-gray-900">
                                                    <?php echo $user['enrolled_classes']; ?>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($user['role'] === 'student' && !empty($recentActivity)): ?>
                        <!-- Recent Activity List -->
                        <div class="mt-8">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul class="divide-y divide-gray-200">
                                    <?php foreach ($recentActivity as $activity): ?>
                                    <li>
                                        <div class="px-4 py-4 sm:px-6">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-indigo-600 truncate">
                                                    <?php echo htmlspecialchars($activity['class_name']); ?>
                                                </p>
                                                <div class="ml-2 flex-shrink-0 flex">
                                                    <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Enrolled
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="mt-2 sm:flex sm:justify-between">
                                                <div class="sm:flex">
                                                    <p class="flex items-center text-sm text-gray-500">
                                                        <i class="fas fa-book-open flex-shrink-0 mr-1.5 text-gray-400"></i>
                                                        <?php echo htmlspecialchars($activity['material_title'] ?? 'No materials yet'); ?>
                                                    </p>
                                                </div>
                                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                                    <i class="fas fa-calendar flex-shrink-0 mr-1.5 text-gray-400"></i>
                                                    <p>
                                                        <?php echo formatDate($activity['enrolled_at'], 'F j, Y'); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Form validation and UI interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Close message alerts
            document.querySelectorAll('.close-message').forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.remove();
                });
            });

            // Auto-hide success messages
            document.querySelectorAll('.bg-green-100').forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => message.remove(), 500);
                }, 3000);
            });

            // Profile form validation
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const email = document.getElementById('email').value;
                    const phone = document.getElementById('phone').value;

                    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        e.preventDefault();
                        alert('Please enter a valid email address');
                        return;
                    }

                    if (!phone.match(/^[+]?[\d\s-]{10,}$/)) {
                        e.preventDefault();
                        alert('Please enter a valid phone number');
                        return;
                    }
                });
            }

            // Password form validation
            const passwordForm = document.getElementById('passwordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;

                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('New passwords do not match');
                        return;
                    }

                    if (newPassword.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long');
                        return;
                    }

                    if (!newPassword.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/)) {
                        e.preventDefault();
                        alert('Password must contain at least one uppercase letter, one lowercase letter, and one number');
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>