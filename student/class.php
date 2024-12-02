<?php
require_once __DIR__ . '/../asset/php/config.php';
require_once __DIR__ . '/../asset/php/db.php';

// Get class ID from URL
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "<script>console.log('Class ID: " . $class_id . "');</script>";

// Verify student's enrollment in this class
$stmt = $pdo->prepare("
    SELECT e.* 
    FROM enrollments e 
    WHERE e.student_id = ? AND e.class_id = ?
");
$stmt->execute([$_SESSION['user_id'], $class_id]);
if (!$stmt->fetch()) {
    echo "<script>console.log('Student not enrolled in class ID: " . $class_id . "');</script>";
    // header('Location: dashboard.php');
    exit;
}
echo "<script>console.log('Student enrolled in class ID: " . $class_id . "');</script>";

// Get class details
$stmt = $pdo->prepare("
    SELECT c.*, u.name as teacher_name
    FROM classes c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();
echo "<script>console.log('Class details: " . json_encode($class) . "');</script>";

// Get class materials
$stmt = $pdo->prepare("
    SELECT * FROM materials 
    WHERE class_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$class_id]);
$materials = $stmt->fetchAll();
echo "<script>console.log('Class materials: " . json_encode($materials) . "');</script>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - EduPortal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-indigo-600">EduPortal</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 px-3 py-2">Dashboard</a>
                        <a href="classes.php" class="border-b-2 border-indigo-500 text-gray-900 px-3 py-2">My Classes</a>
                        <a href="profile.php" class="text-gray-500 hover:text-gray-700 px-3 py-2">Profile</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <button id="logoutBtn" class="text-gray-500 hover:text-gray-700">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Class Header -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($class['name']) ?></h2>
                <p class="mt-1 text-sm text-gray-500">
                    Instructor: <?= htmlspecialchars($class['teacher_name']) ?>
                </p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <p class="text-gray-700"><?= htmlspecialchars($class['description']) ?></p>
            </div>
        </div>

        <!-- Materials Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium text-gray-900">Course Materials</h3>
            </div>
            <div class="border-t border-gray-200">
                <?php if (empty($materials)): ?>
                    <div class="px-4 py-5 sm:px-6 text-gray-500">
                        No materials available yet.
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($materials as $material): ?>
                            <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <!-- Icon based on material type -->
                                        <div class="flex-shrink-0">
                                            <?php if ($material['type'] === 'pdf'): ?>
                                                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            <?php elseif ($material['type'] === 'link'): ?>
                                                <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <h4 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($material['title']) ?></h4>
                                            <p class="text-sm text-gray-500">
                                                Added on <?= date('F j, Y', strtotime($material['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <a href="<?= htmlspecialchars($material['content']) ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200">
                                        Access Material
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('../asset/php/logout.php');
                const data = await response.json();
                if (data.success) {
                    console.log('Logout successful');
                    window.location.href = '../login.php';
                } else {
                    console.log('Logout failed');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>