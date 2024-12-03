<?php
// Enable error reporting and logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
ini_set('error_log', $logDir . '/error.log');

// Start session
session_start();

// Debug log
error_log('Session started with data: ' . print_r($_SESSION, true));

// Include required files
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

// Check session and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
} elseif ($_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Test database connection
try {
    $pdo->query('SELECT 1');
    error_log('Database connection successful');
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection error. Please contact administrator.');
}

// Get user data
try {
    $stmt = $pdo->prepare('
        SELECT 
            u.id,
            u.name,
            u.email,
            u.last_access,
            COUNT(DISTINCT e.class_id) as enrolled_count
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        WHERE u.id = ? AND u.role = "student"
        GROUP BY u.id
    ');
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error_log('No user found with ID: ' . $_SESSION['user_id']);
        die('User data not found. Please try logging in again.');
    }

    // Get enrolled classes
    $stmt = $pdo->prepare('
        SELECT 
            c.id, 
            c.name, 
            c.description,
            COUNT(m.id) as material_count
        FROM classes c
        LEFT JOIN enrollments e ON c.id = e.class_id
        LEFT JOIN materials m ON c.id = m.class_id
        WHERE e.student_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ');
    
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Classes fetched: ' . count($classes));

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    die('Error loading data. Please try again later.');
}

// Handle password update
$passwordUpdateMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updatePassword') {
    try {
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            $passwordUpdateMessage = 'New passwords do not match';
        } else {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch();

            if (password_verify($currentPassword, $userData['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                $passwordUpdateMessage = 'Password updated successfully!';
            } else {
                $passwordUpdateMessage = 'Current password is incorrect';
            }
        }
    } catch (PDOException $e) {
        error_log('Password Update Error: ' . $e->getMessage());
        $passwordUpdateMessage = 'Database error occurred';
    }
}

function formatDateTime($datetime) {
    if (!$datetime) return 'Never';
    return date('M j, Y g:i A', strtotime($datetime));
}

// Debug log user data
error_log('User data loaded: ' . print_r($user, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($user['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">Student Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="settingsBtn" type="button" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                    <a href="../auth/logout.php" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
            <p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <!-- Stats Cards -->
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500">Enrolled Classes</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $user['enrolled_count']; ?></dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500">Last Active</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo formatDateTime($user['last_access']); ?></dd>
                </div>
            </div>
        </div>

        <!-- Classes List -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Your Classes</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php if (empty($classes)): ?>
                <div class="col-span-full">
                    <div class="bg-white shadow rounded-lg p-6 text-center">
                        <div class="text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No classes enrolled</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You haven't enrolled in any classes yet. Please contact your administrator for enrollment.
                            </p>
                        </div>
                        <a href="../contact.php" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Contact Admin
                        </a>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($classes as $course): ?>
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['name']); ?></h4>
                                <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($course['description'] ?? 'No description available'); ?></p>
                                <p class="mt-1 text-sm text-gray-500"><?php echo $course['material_count']; ?> materials available</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <a href="class.php?id=<?php echo $course['id']; ?>" 
                           class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            View materials
                            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Change Password</h3>
                <button id="closeModal" type="button" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <?php if ($passwordUpdateMessage): ?>
            <div class="mb-4 p-4 rounded-md <?php echo strpos($passwordUpdateMessage, 'successfully') !== false ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
                <?php echo htmlspecialchars($passwordUpdateMessage); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="updatePassword">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" name="currentPassword" required
                           class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="newPassword" required
                           class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="confirmPassword" required
                           class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                </div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('passwordModal');
        const settingsBtn = document.getElementById('settingsBtn');
        const closeModal = document.getElementById('closeModal');

        if (modal && settingsBtn && closeModal) {
            settingsBtn.addEventListener('click', () => {
                modal.classList.remove('hidden');
            });

            closeModal.addEventListener('click', () => {
                modal.classList.add('hidden');
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }

        // Auto-hide success message
        const successMessage = document.querySelector('.bg-green-50');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }

        // Log any errors for debugging
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
            return false;
        };
    </script>
</body>
</html>