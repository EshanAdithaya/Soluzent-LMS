<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_only_cookies' => true
]);

require_once __DIR__ . '/../asset/php/config.php';
require_once __DIR__ . '/../asset/php/db.php';
require_once 'session.php';

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// User ID from session
$userId = $_SESSION['user_id'];

try {
    // Get user data
    $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get enrolled classes with detailed information
    $stmt = $pdo->prepare('
        SELECT 
            c.id,
            c.name,
            c.description,
            u.name as teacher_name,
            e.enrolled_at,
            COUNT(DISTINCT m.id) as material_count,
            (
                SELECT COUNT(*) 
                FROM materials m2 
                WHERE m2.class_id = c.id 
                AND m2.created_at > COALESCE(
                    (SELECT last_access FROM users WHERE id = ?), 
                    e.enrolled_at
                )
            ) as new_materials_count
        FROM classes c
        JOIN enrollments e ON c.id = e.class_id
        JOIN users u ON e.teacher_id = u.id
        LEFT JOIN materials m ON c.id = m.class_id
        WHERE e.student_id = ?
        GROUP BY c.id, c.name, c.description, u.name, e.enrolled_at
        ORDER BY e.enrolled_at DESC
    ');
    $stmt->execute([$userId, $userId]);
    $enrolledClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('Error loading class data. Please try again later.');
}

function formatDate($datetime) {
    return date('M j, Y', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrolled Classes - <?php echo htmlspecialchars($user['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'student-header.php'; ?>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8 px-4 sm:px-0">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
                    My Enrolled Classes
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    View and access all your enrolled classes
                </p>
            </div>
        </div>

        <!-- Classes Grid -->
        <div class="px-4 sm:px-0">
            <?php if (empty($enrolledClasses)): ?>
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No classes found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        You haven't enrolled in any classes yet.
                    </p>
                    <div class="mt-6">
                        <a href="../contact.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Contact Administrator
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($enrolledClasses as $class): ?>
                        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </h3>
                                    <?php if ($class['new_materials_count'] > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?php echo $class['new_materials_count']; ?> new
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($class['description'] ?? 'No description available'); ?>
                                </p>
                                
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Teacher: <?php echo htmlspecialchars($class['teacher_name']); ?>
                                    </div>
                                    
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                        <?php echo $class['material_count']; ?> materials
                                    </div>
                                    
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Enrolled: <?php echo formatDate($class['enrolled_at']); ?>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <a href="class.php?id=<?php echo $class['id']; ?>" 
                                       class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        View Class Materials
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Add any necessary JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any interactive features
        });
    </script>
</body>
</html>