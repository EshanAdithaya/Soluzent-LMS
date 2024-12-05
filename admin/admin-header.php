<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_profile_page = ($current_page === 'profile.php');
$is_dashboard_page = ($current_page === 'dashboard.php');
$admin_prefix = $is_profile_page ? 'admin/' : '';
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
                            <h1 class="text-xl font-bold text-indigo-600">EduPortal Admin</h1>
                        <?php else: ?>
                            <a href="<?php echo $admin_prefix; ?>dashboard.php">
                                <h1 class="text-xl font-bold text-indigo-600">EduPortal Admin</h1>
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
                        ($is_profile_page ? '' : '../') . 'student/Dashboard.php' => 'Student Dashboard'
                    ];

                    foreach ($nav_items as $page => $label) {
                        $active = ($current_page === basename($page)) ? 
                            'border-indigo-500 text-gray-900' : 
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
                        echo "<a href='$page' class='inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium $active'>$label</a>";
                    }
                    ?>
                </div>

                <!-- Profile Dropdown -->
                <div class="flex items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span id="adminName" class="text-gray-700"></span>
                            <a href="<?php echo $is_profile_page ? '' : '../'; ?>profile.php" class="text-gray-600 hover:text-gray-900">My Profile</a>
                            <a href="<?php echo $is_profile_page ? 'admin/' : ''; ?>../asset/php/logout.php"> <button class="text-gray-600 hover:text-gray-900">Logout</button> </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</body>
</html>