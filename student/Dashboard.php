<?php
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

// Check if user is logged in and verify role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
} elseif ($_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $response = ['success' => false, 'message' => ''];

    if ($_GET['action'] === 'getDashboardData') {
        try {
            $userId = $_SESSION['user_id'];
            
            // Get student info and enrolled class count
            $stmt = $pdo->prepare('
                SELECT u.name, u.email, u.last_access,
                    (SELECT COUNT(*) FROM enrollments WHERE student_id = u.id) as enrolled_count
                FROM users u 
                WHERE u.id = ? AND u.role = "student"
            ');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
    
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
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
            $classes = $stmt->fetchAll();
    
            echo json_encode([
                'success' => true,
                'data' => [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'enrolledClasses' => $classes,
                    'lastAccess' => $user['last_access'],
                    'enrolledCount' => $user['enrolled_count']
                ]
            ]);
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'updatePassword') {
        try {
            $userId = $_SESSION['user_id'];
            $currentPassword = $_POST['currentPassword'];
            $newPassword = $_POST['newPassword'];

            // Verify current password
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND role = "student"');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (password_verify($currentPassword, $user['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([$hashedPassword, $userId]);
                
                echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            }
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            error_log($e->getMessage());
            exit;
        }
    }
}

// Get initial data for page load
try {
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare('
        SELECT name, email, last_access 
        FROM users 
        WHERE id = ? AND role = "student"
    ');
    $stmt->execute([$userId]);
    $initialData = $stmt->fetch();
} catch (PDOException $e) {
    $initialData = ['name' => 'Student', 'email' => '', 'last_access' => null];
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .skeleton {
            animation: skeleton-loading 1s linear infinite alternate;
        }
        @keyframes skeleton-loading {
            0% { background-color: rgba(199, 199, 199, 0.1); }
            100% { background-color: rgba(199, 199, 199, 0.3); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>
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
                    <a href="../asset/php/logout.php" class="text-gray-500 hover:text-gray-700">
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
        <div id="studentInfo" class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($initialData['name']); ?></h2>
            <p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars($initialData['email']); ?></p>
        </div>

        <!-- Stats Cards -->
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500">Enrolled Classes</dt>
                    <dd id="enrolledCount" class="mt-1 text-3xl font-semibold text-gray-900 skeleton">-</dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500">Last Active</dt>
                    <dd id="lastAccess" class="mt-1 text-3xl font-semibold text-gray-900 skeleton">-</dd>
                </div>
            </div>
        </div>

        <!-- Classes List -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Your Classes</h3>
            <div id="classesList" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div class="col-span-full">
                    <div class="animate-pulse bg-white shadow rounded-lg p-6">
                        Loading classes...
                    </div>
                </div>
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
            <form id="passwordForm" class="space-y-4">
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
                <div id="passwordError" class="text-red-600 text-sm hidden"></div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function formatDateTime(dateString) {
            if (!dateString) return 'Never';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
        }

        async function fetchDashboardData() {
            try {
                const response = await fetch('?action=getDashboardData');
                const result = await response.json();
                
                if (result.success) {
                    updateDashboard(result.data);
                } else {
                    console.error('Error:', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function updateDashboard(data) {
            // Update student info
            const studentInfo = document.getElementById('studentInfo');
            studentInfo.innerHTML = `
                <h2 class="text-2xl font-bold text-gray-900 fade-in">Welcome, ${data.name}</h2>
                <p class="mt-1 text-sm text-gray-600 fade-in">${data.email}</p>
            `;

            // Update enrolled classes count
            const enrolledCount = document.getElementById('enrolledCount');
            enrolledCount.textContent = data.enrolledCount || '0';
            enrolledCount.classList.remove('skeleton');

            // Update last access time
            const lastAccess = document.getElementById('lastAccess');
            lastAccess.textContent = formatDateTime(data.lastAccess);
            lastAccess.classList.remove('skeleton');

            // Update classes list
            const classesList = document.getElementById('classesList');
            if (data.enrolledClasses && data.enrolledClasses.length > 0) {
                classesList.innerHTML = data.enrolledClasses.map(course => `
                    <div class="bg-white shadow rounded-lg p-6 fade-in">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900">${course.name}</h4>
                                <p class="mt-2 text-sm text-gray-600">${course.description || 'No description available'}</p>
                                <p class="mt-1 text-sm text-gray-500">${course.material_count} materials available</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <a href="class.php?id=${course.id}" 
                           class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            View materials
                            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                `).join('');
            } else {
                classesList.innerHTML = `
                    <div class="col-span-full">
                        <div class="bg-white shadow rounded-lg p-6 text-center fade-in">
                            <div class="text-gray-500 mb-4">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No classes enrolled</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    You haven't enrolled in any classes yet. Please contact your administrator for enrollment.
                                </p>
                            </div>
                            <button onclick="window.location.href='../contact.php'" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Contact Admin
                                <svg class="ml-2 -mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        // Password modal functionality
        const modal = document.getElementById('passwordModal');
        const settingsBtn = document.getElementById('settingsBtn');
        const closeModal = document.getElementById('closeModal');
        const passwordForm = document.getElementById('passwordForm');
        const passwordError = document.getElementById('passwordError');

        settingsBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });

        closeModal.addEventListener('click', () => {
            modal.classList.add('hidden');
            passwordForm.reset();
            passwordError.classList.add('hidden');
        });

        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(passwordForm);
            
            if (formData.get('newPassword') !== formData.get('confirmPassword')) {
                passwordError.textContent = 'New passwords do not match';
                passwordError.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch('?action=updatePassword', {
                    method: 'POST',
                    body: new URLSearchParams({
                        currentPassword: formData.get('currentPassword'),
                        newPassword: formData.get('newPassword')
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Password updated successfully!');
                    modal.classList.add('hidden');
                    passwordForm.reset();
                } else {
                    passwordError.textContent = result.message;
                    passwordError.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
                passwordError.textContent = 'An error occurred. Please try again.';
                passwordError.classList.remove('hidden');
            }
        });

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                passwordForm.reset();
                passwordError.classList.add('hidden');
            }
        });

        // Load dashboard data on page load
        document.addEventListener('DOMContentLoaded', fetchDashboardData);

        // Add automatic session refresh every 5 minutes
        setInterval(() => {
            fetchDashboardData();
        }, 300000); // 5 minutes in milliseconds

        // Handle visibility change to refresh data when tab becomes active
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                fetchDashboardData();
            }
        });
    </script>
</body>
</html>