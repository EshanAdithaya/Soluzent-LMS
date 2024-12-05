<?php

require_once __DIR__ . '/../asset/php/config.php';
require_once __DIR__ . '/../asset/php/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    header('Location: ../login.php');
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
    SELECT name, email, DATE_FORMAT(created_at, \'%Y-%m-%d\') as joined_date
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

    // echo json_encode([
    //     'success' => true,
    //     'totalStudents' => $totalStudents,
    //     'activeClasses' => $activeClasses,
    //     'totalMaterials' => $totalMaterials,
    //     'newStudents' => $newStudents,
    //     'recentStudents' => $recentStudents,
    //     'recentMaterials' => $recentMaterials
    // ]);
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage()); // Add this line
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log($e->getMessage());
}
?>
<?php require_once 'admin-header.php'; ?>
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Welcome Section -->
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-2xl font-bold text-gray-900">Admin Dashboard</h2>
        <div class="flex items-center justify-between">
            <p class="mt-1 text-sm text-gray-600">Overview of platform statistics</p>
            <p class="text-lg text-gray-700">Welcome, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span></p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Total Students</dt>
                <dd id="totalStudents" class="mt-1 text-3xl font-semibold text-gray-900"><?php echo($totalStudents) ?></dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Active Classes</dt>
                <dd id="activeClasses" class="mt-1 text-3xl font-semibold text-gray-900"><?php echo($activeClasses) ?></dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">Total Materials</dt>
                <dd id="totalMaterials" class="mt-1 text-3xl font-semibold text-gray-900"><?php echo($totalMaterials) ?></dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">New Students (This Week)</dt>
                <dd id="newStudents" class="mt-1 text-3xl font-semibold text-gray-900"><?php echo($newStudents) ?></dd>
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
                <?php
if (!empty($recentStudents)) {
    foreach ($recentStudents as $rstudents) {
        ?>
        <div class="flex items-center justify-between py-3 border-b">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rstudents['name']); ?></p>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($rstudents['email']); ?></p>
                </div>
                <span class="text-sm text-gray-500"><?php echo htmlspecialchars($rstudents['joined_date']); ?></span>
            </div>
        
        <?php
    }
} else {
    echo '<div class="animate-pulse">No recent students available.</div>';
}

                ?>
                    
                </div>
            </div>
        </div>

        <!-- Recent Materials -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900">Recent Materials</h3>
                <div class="mt-4" id="recentMaterialsList">
                <?php 
                if (!empty($recentMaterials)) {
                    foreach ($recentMaterials as $material) {
                        ?>
                        <div class="flex items-center justify-between py-3 border-b">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($material['title']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($material['class_name']); ?></p>
                            </div>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $material['type'] === 'pdf' ? 'red' : 'blue'; ?>-100 text-<?php echo $material['type'] === 'pdf' ? 'red' : 'blue'; ?>-800">
                                <?php echo htmlspecialchars($material['type']); ?>
                            </span>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="animate-pulse">No recent materials available.</div>';
                }
                
                ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>


