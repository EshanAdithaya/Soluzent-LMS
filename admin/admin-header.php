<?php
// include_once 'adminSession.php';

// Simple page and role detection
$current_page = basename($_SERVER['REQUEST_URI']);
$is_teacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$portal_title = $is_teacher ? 'EduPortal Teacher' : 'EduPortal Admin';

// Determine base path for links
$base_path = '';
if (strpos($current_page, 'profile.php') !== false) {
    $base_path = 'admin/';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduPortal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- <script src="../asset/js/devtools-prevention.js"></script> -->
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo $base_path; ?>dashboard.php" class="text-xl font-bold text-indigo-600">
                            <?php echo $portal_title; ?>
                        </a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:space-x-8 items-center">
                    <?php
                    // Define navigation items
                    $nav_items = [
                        'dashboard.php' => 'Dashboard',
                        'classes.php' => 'Classes',
                        'users.php' => 'Students',
                        'materials.php' => 'Materials'
                    ];

                    // Add Teacher Applications link only for admin
                    if ($_SESSION['role'] !== 'teacher') {
                        $nav_items['teacher-applications.php'] = 'Teacher Applications';
                    }

                    // Render navigation items
                    foreach ($nav_items as $page => $label) {
                        $url = $base_path . $page;
                        $active = ($current_page === $page) ? 
                            'border-indigo-500 text-gray-900' : 
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
                        ?>
                        <a href="<?php echo $url; ?>" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?php echo $active; ?>">
                            <?php echo $label; ?>
                        </a>
                        <?php
                    }
                    ?>
                    
                    <!-- Student Dashboard Link -->
                    <a href="../student/Dashboard.php" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700">
                        Student Dashboard
                    </a>
                </div>

                <!-- Profile Actions -->
                <div class="flex items-center space-x-6">
                    <!-- Profile Link -->
                    <a href="../profile.php" class="text-gray-600 hover:text-gray-900 relative group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="absolute hidden group-hover:block -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded">Profile</span>
                    </a>

                    <!-- Logout Link -->
                    <a href="../asset/php/logout.php" class="text-gray-600 hover:text-gray-900 relative group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="absolute hidden group-hover:block -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</body>
</html>