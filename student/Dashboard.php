<?php
session_start();
error_log('Session started for user');
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

// Check if user is logged in and verify role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    error_log('User not logged in, redirecting to login page');
    header('Location: ../login.php');
    exit;
} elseif ($_SESSION['role'] !== 'student') {
    error_log('Invalid role detected: ' . $_SESSION['role']);
    header('Location: ../login.php');
    exit;
}

// Function to get dashboard data
function getDashboardData($pdo, $userId) {
    error_log('Fetching dashboard data for user ID: ' . $userId);
    try {
        // Get student info and enrolled class count
        $stmt = $pdo->prepare('
            SELECT u.name, u.email, u.last_access,
                (SELECT COUNT(*) FROM enrollments WHERE student_id = u.id) as enrolled_count
            FROM users u 
            WHERE u.id = ? AND u.role = "student"
        ');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Get enrolled classes with material count
        $stmt = $pdo->prepare('
            SELECT c.id, c.name, c.description,
                (SELECT COUNT(*) FROM materials WHERE class_id = c.id) as material_count
            FROM classes c
            JOIN enrollments e ON c.id = e.class_id
            WHERE e.student_id = ?
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([$userId]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'user' => $user,
            'classes' => $classes
        ];
    } catch (PDOException $e) {
        error_log('Dashboard Data Error: ' . $e->getMessage());
        return false;
    }
}

// Function to format date time
function formatDateTime($datetime) {
    error_log('Formatting datetime: ' . $datetime);
    if (!$datetime) return 'Never';
    return date('M j, Y g:i A', strtotime($datetime));
}

// Handle password update
$passwordUpdateMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updatePassword') {
    error_log('Password update attempt for user ID: ' . $_SESSION['user_id']);
    try {
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            $passwordUpdateMessage = 'New passwords do not match';
        } else {
            // Verify current password
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND role = "student"');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (password_verify($currentPassword, $user['password'])) {
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

// Get dashboard data
error_log('Attempting to load dashboard data for user ID: ' . $_SESSION['user_id']);
$dashboardData = getDashboardData($pdo, $_SESSION['user_id']);
if (!$dashboardData) {
    error_log('Failed to load dashboard data for user ID: ' . $_SESSION['user_id']);
    die('Error loading dashboard data');
}

$user = $dashboardData['user'];
$classes = $dashboardData['classes'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
                    <button id="settingsBtn" class="text-gray-500 hover:text-gray-700">
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
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        <?php echo $user['enrolled_count']; ?>
                    </dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500">Last Active</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        <?php echo formatDateTime($user['last_access']); ?>
                    </dd>
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
                <button id="closeModal" class="text-gray-400 hover:text-gray-500">
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
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="newPassword" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="confirmPassword" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <script>
        console.log('Dashboard script initialized');
        
        // Modal elements
        const modal = document.getElementById('passwordModal');
        const settingsBtn = document.getElementById('settingsBtn');
        const closeModal = document.getElementById('closeModal');
        
        console.log('Modal elements initialized:', { 
            modalExists: !!modal, 
            settingsBtnExists: !!settingsBtn, 
            closeModalExists: !!closeModal 
        });

        // Modal open handler
        settingsBtn.addEventListener('click', () => {
            console.log('Settings button clicked - Opening password modal');
            modal.classList.remove('hidden');
            console.log('Modal visibility state:', !modal.classList.contains('hidden'));
        });

        // Modal close handler
        closeModal.addEventListener('click', () => {
            console.log('Close button clicked - Closing modal');
            modal.classList.add('hidden');
            console.log('Modal visibility state:', !modal.classList.contains('hidden'));
        });

        // Outside modal click handler
        modal.addEventListener('click', (e) => {
            console.log('Modal click detected', {
                targetElement: e.target.tagName,
                clickedOnModal: e.target === modal
            });
            
            if (e.target === modal) {
                console.log('Click outside modal content - Closing modal');
                modal.classList.add('hidden');
                console.log('Modal visibility state:', !modal.classList.contains('hidden'));
            }
        });

        // Success message auto-hide
        const message = document.querySelector('.bg-green-50');
        if (message) {
            console.log('Success message found:', message.textContent.trim());
            console.log('Setting up auto-hide timer for success message');
            
            setTimeout(() => {
                console.log('Auto-hide timer triggered - Hiding success message');
                message.style.display = 'none';
                console.log('Success message hidden');
            }, 3000);
        } else {
            console.log('No success message found on page');
        }

        // Form submission logging
        const passwordForm = document.querySelector('form');
        if (passwordForm) {
            console.log('Password form found:', {
                formElements: passwordForm.elements.length,
                hasRequiredFields: passwordForm.querySelector('[required]') !== null
            });

            passwordForm.addEventListener('submit', (e) => {
                console.log('Password form submission initiated', {
                    timestamp: new Date().toISOString(),
                    formData: new FormData(passwordForm)
                });
            });
        } else {
            console.log('No password form found on page');
        }

        // Page load handler
        window.addEventListener('load', () => {
            console.log('Dashboard page fully loaded', {
                url: window.location.href,
                timestamp: new Date().toISOString()
            });
            
            console.log('User statistics:', {
                enrolledClasses: <?php echo $user['enrolled_count']; ?>,
                lastAccess: <?php echo json_encode(formatDateTime($user['last_access'])); ?>,
                screenResolution: `${window.innerWidth}x${window.innerHeight}`
            });

            // Log all available classes
            const classElements = document.querySelectorAll('.bg-white.shadow.rounded-lg');
            console.log('Classes displayed:', {
                totalClasses: classElements.length,
                classCards: Array.from(classElements).map(el => ({
                    title: el.querySelector('h4')?.textContent,
                    materialsCount: el.querySelector('.text-gray-500')?.textContent
                }))
            });
        });

        // Add input field monitoring
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            input.addEventListener('focus', (e) => {
                console.log('Password field focused:', {
                    fieldName: e.target.name,
                    timestamp: new Date().toISOString()
                });
            });

            input.addEventListener('blur', (e) => {
                console.log('Password field blur:', {
                    fieldName: e.target.name,
                    hasValue: e.target.value.length > 0,
                    timestamp: new Date().toISOString()
                });
            });
        });

        // Monitor all button clicks
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', (e) => {
                console.log('Button clicked:', {
                    buttonText: button.textContent.trim(),
                    buttonType: button.type,
                    timestamp: new Date().toISOString()
                });
            });
        });
    </script>
</body>
</html>