<?php
session_start();

require '../asset/php/config.php';
require '../asset/php/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Prepare statements for secure data fetching
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
    $stmt->execute(['role' => 'student']);
    $totalStudents = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM classes');
    $stmt->execute();
    $activeClasses = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM materials');
    $stmt->execute();
    $totalMaterials = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM users 
        WHERE role = :role 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
    ');
    $stmt->execute(['role' => 'student']);
    $newStudents = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT name, email, DATE_FORMAT(created_at, "%Y-%m-%d") as joined_date
        FROM users 
        WHERE role = :role
        ORDER BY created_at DESC 
        LIMIT 5
    ');
    $stmt->execute(['role' => 'student']);
    $recentStudents = $stmt->fetchAll();

    $stmt = $pdo->prepare('
        SELECT m.title, m.type, c.name as class_name
        FROM materials m
        JOIN classes c ON m.class_id = c.id
        ORDER BY m.created_at DESC
        LIMIT 5
    ');
    $stmt->execute();
    $recentMaterials = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'totalStudents' => $totalStudents,
        'activeClasses' => $activeClasses,
        'totalMaterials' => $totalMaterials,
        'newStudents' => $newStudents,
        'recentStudents' => $recentStudents,
        'recentMaterials' => $recentMaterials
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log($e->getMessage());
}
?>
<?php require_once 'admin-header.php'; ?>
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Welcome Section -->
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-2xl font-bold text-gray-900">Admin Dashboard</h2>
        <p class="mt-1 text-sm text-gray-600">Overview of platform statistics</p>
    </div>

    <!-- Stats Cards -->
    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Total Students</dt>
                <dd id="totalStudents" class="mt-1 text-3xl font-semibold text-gray-900">0</dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Active Classes</dt>
                <dd id="activeClasses" class="mt-1 text-3xl font-semibold text-gray-900">0</dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Total Materials</dt>
                <dd id="totalMaterials" class="mt-1 text-3xl font-semibold text-gray-900">0</dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">New Students (This Week)</dt>
                <dd id="newStudents" class="mt-1 text-3xl font-semibold text-gray-900">0</dd>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Students -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900">Recent Students</h3>
                <div class="mt-4" id="recentStudentsList">
                    <div class="animate-pulse">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Recent Materials -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900">Recent Materials</h3>
                <div class="mt-4" id="recentMaterialsList">
                    <div class="animate-pulse">Loading...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function fetchDashboardData() {
        try {
            const response = await fetch('dashboard.php');
            if (!response.ok) {
                throw new Error('Failed to load data: ' + response.statusText);
            }
            const data = await response.json();

            if (data.success) {
                updateDashboard(data);
            } else {
                alert('Error loading dashboard data');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while fetching data');
        }
    }

    function updateDashboard(data) {
        // Update stats
        document.getElementById('totalStudents').textContent = data.totalStudents;
        document.getElementById('activeClasses').textContent = data.activeClasses;
        document.getElementById('totalMaterials').textContent = data.totalMaterials;
        document.getElementById('newStudents').textContent = data.newStudents;

        // Update recent students list
        const studentsList = document.getElementById('recentStudentsList');
        studentsList.innerHTML = data.recentStudents.map(student => `
            <div class="flex items-center justify-between py-3 border-b">
                <div>
                    <p class="text-sm font-medium text-gray-900">${student.name}</p>
                    <p class="text-sm text-gray-500">${student.email}</p>
                </div>
                <span class="text-sm text-gray-500">${student.joined_date}</span>
            </div>
        `).join('');

        // Update recent materials list
        const materialsList = document.getElementById('recentMaterialsList');
        materialsList.innerHTML = data.recentMaterials.map(material => `
            <div class="flex items-center justify-between py-3 border-b">
                <div>
                    <p class="text-sm font-medium text-gray-900">${material.title}</p>
                    <p class="text-sm text-gray-500">${material.class_name}</p>
                </div>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${material.type === 'pdf' ? 'red' : 'blue'}-100 text-${material.type === 'pdf' ? 'red' : 'blue'}-800">
                    ${material.type}
                </span>
            </div>
        `).join('');
    }

    // Load dashboard data on page load
    document.addEventListener('DOMContentLoaded', fetchDashboardData);
</script>
