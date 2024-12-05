<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_profile_page = ($current_page === 'profile.php');
$is_dashboard_page = ($current_page === 'dashboard.php');
$admin_prefix = $is_profile_page ? 'admin/' : '';
$portal_title = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher' ? 'EduPortal Teacher' : 'EduPortal Admin';

include_once 'adminSession.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduPortal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo htmlspecialchars($baseUrl ?? ''); ?>../asset/js/devtools-prevention.js"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <?php if ($is_dashboard_page): ?>
                            <h1 class="text-xl font-bold text-indigo-600"><?php echo $portal_title; ?></h1>
                        <?php else: ?>
                            <a href="<?php echo $admin_prefix; ?>dashboard.php">
                                <h1 class="text-xl font-bold text-indigo-600"><?php echo $portal_title; ?></h1>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:space-x-8 items-center">
                    <?php
                    $nav_items = [
                        $admin_prefix . 'classes.php' => 'Classes',
                        $admin_prefix . 'students.php' => 'Students',
                        $admin_prefix . 'materials.php' => 'Materials',
                    ];
                    
                    // Only show Teacher Applications link if user is not a teacher
                    if ($_SESSION['role'] !== 'teacher') {
                        $nav_items[$admin_prefix . 'teacher-applications.php'] = 'Teacher_Applications';
                    } else {
                        // Add Teacher Students link for teachers
                        $nav_items[$admin_prefix . 'teacher-students.php'] = 'Teacher Students';
                    }
                    
                    $nav_items[($is_profile_page ? '' : '../') . 'student/Dashboard.php'] = 'Student Dashboard';

                    foreach ($nav_items as $page => $label) {
                        $active = ($current_page === basename($page)) ? 
                            'border-indigo-500 text-gray-900' : 
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
                        echo "<a href='$page' class='inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium $active'>$label</a>";
                    }
                    ?>
                </div>

                <!-- Profile Actions -->
                <div class="flex items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-6">
                            <a href="<?php echo $is_profile_page ? '' : '../'; ?>profile.php" class="text-gray-600 hover:text-gray-900 relative group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="absolute hidden group-hover:block -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded">Profile</span>
                            </a>
                            <a href="<?php echo $is_profile_page ? 'admin/' : ''; ?>../asset/php/logout.php" class="text-gray-600 hover:text-gray-900 relative group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="absolute hidden group-hover:block -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</body>
</html>