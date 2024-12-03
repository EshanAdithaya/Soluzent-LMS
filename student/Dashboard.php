<?php

require_once __DIR__ . '/../asset/php/config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // echo '<script>alert("Please login to access the dashboard."); window.location.href = "../login.php";</script>';
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    // Get student info
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Get enrolled classes
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.description
        FROM classes c
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.student_id = ?
    ');
    $stmt->execute([$userId]);
    $classes = $stmt->fetchAll();

    // Get recent materials
    $stmt = $pdo->prepare('
        SELECT m.id, m.title, m.type, m.content, c.name as class_name
        FROM materials m
        JOIN classes c ON m.class_id = c.id
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.student_id = ?
        ORDER BY m.created_at DESC
        LIMIT 5
    ');
    $stmt->execute([$userId]);
    $materials = $stmt->fetchAll();

    // Count total materials
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM materials m
        JOIN classes c ON m.class_id = c.id
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.student_id = ?
    ');
    $stmt->execute([$userId]);
    $totalMaterials = $stmt->fetch()['total'];

    // Update last access time
    $stmt = $pdo->prepare('UPDATE users SET last_access = NOW() WHERE id = ?');
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'name' => $user['name'],
        'enrolledClasses' => $classes,
        'recentMaterials' => $materials,
        'totalMaterials' => $totalMaterials,
        'lastAccess' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
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
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
<?php require_once 'student-header.php'; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-gray-900">Welcome back, <span id="welcomeName"></span></h2>
            <p class="mt-1 text-sm text-gray-600">Here are your enrolled classes and materials</p>
        </div>

        <!-- Stats Section -->
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Classes</dt>
                    <dd id="enrolledCount" class="mt-1 text-3xl font-semibold text-gray-900">0</dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Available Materials</dt>
                    <dd id="materialsCount" class="mt-1 text-3xl font-semibold text-gray-900">0</dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Last Access</dt>
                    <dd id="lastAccess" class="mt-1 text-3xl font-semibold text-gray-900">Today</dd>
                </div>
            </div>
        </div>

        <!-- Enrolled Classes -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900">Your Classes</h3>
            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" id="classesList">
                <!-- Classes will be inserted here -->
            </div>
        </div>

        <!-- Recent Materials -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900">Recent Materials</h3>
            <div class="mt-4" id="materialsList">
                <!-- Materials will be inserted here -->
            </div>
        </div>
    </main>

    <script>
        // Fetch student data and update UI
        async function fetchDashboardData() {
            try {
                const response = await fetch('Dashboard.php');
                const data = await response.json();

                if (data.success) {
                    updateDashboard(data);
                } else {
                    alert('Error loading dashboard data');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function updateDashboard(data) {
            // Update welcome section
            document.getElementById('studentName').textContent = data.name;
            document.getElementById('welcomeName').textContent = data.name;

            // Update stats
            document.getElementById('enrolledCount').textContent = data.enrolledClasses.length;
            document.getElementById('materialsCount').textContent = data.totalMaterials;
            document.getElementById('lastAccess').textContent = data.lastAccess;

            // Update classes list
            const classesList = document.getElementById('classesList');
            classesList.innerHTML = data.enrolledClasses.map(course => `
                <div class="bg-white shadow rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900">${course.name}</h4>
                    <p class="mt-2 text-sm text-gray-600">${course.description}</p>
                    <div class="mt-4">
                        <a href="class.php?id=${course.id}" 
                           class="text-indigo-600 hover:text-indigo-900">
                            View materials →
                        </a>
                    </div>
                </div>
            `).join('');

            // Update materials list
            const materialsList = document.getElementById('materialsList');
            materialsList.innerHTML = data.recentMaterials.map(material => `
                <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-4">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h4 class="text-md font-medium text-gray-900">${material.title}</h4>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                       ${getTypeColor(material.type)}">
                                ${material.type}
                            </span>
                        </div>
                        <div class="mt-2 flex justify-between">
                            <p class="text-sm text-gray-600">${material.class_name}</p>
                            <a href="${material.content}" class="text-sm text-indigo-600 hover:text-indigo-900">
                                Access Material
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getTypeColor(type) {
            const colors = {
                'pdf': 'bg-red-100 text-red-800',
                'link': 'bg-blue-100 text-blue-800',
                'image': 'bg-green-100 text-green-800'
            };
            return colors[type] || 'bg-gray-100 text-gray-800';
        }

        // Handle logout
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('auth/logout.php');
                const data = await response.json();
                if (data.success) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Load dashboard data on page load
        document.addEventListener('DOMContentLoaded', fetchDashboardData);
    </script>
</body>
</html>


