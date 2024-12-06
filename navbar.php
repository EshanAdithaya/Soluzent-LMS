<?php
// Add this at the top of the file
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}
$currentPage = getCurrentPage();
?>

<!-- Navigation -->
<nav class="bg-white shadow-lg fixed w-full z-10">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">SOLUZENT LMS</h1>
                </div>
            </div>

            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Home</a>
                <a href="about.php" class="<?= $currentPage === 'about.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">About Us</a>
                <a href="features.php" class="<?= $currentPage === 'features.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Features</a>
                <a href="pricing.php" class="<?= $currentPage === 'pricing.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Pricing</a>
                <a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Contact</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php' ?>" 
                       class="<?= strpos($currentPage, 'dashboard.php') !== false ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Dashboard</a>
                    <a href="asset/php/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="<?= $currentPage === 'login.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Login</a>
                    <a href="signup.php" 
                       class="<?= $currentPage === 'signup.php' ? 'bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700' ?> text-white px-4 py-2 rounded-md">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button class="mobile-menu-button">
                    <i class="fas fa-bars text-gray-500 text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="hidden mobile-menu md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="index.php" class="block px-3 py-2 <?= $currentPage === 'index.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Home</a>
            <a href="about.php" class="block px-3 py-2 <?= $currentPage === 'about.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">About Us</a>
            <a href="features.php" class="block px-3 py-2 <?= $currentPage === 'features.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Features</a>
            <a href="pricing.php" class="block px-3 py-2 <?= $currentPage === 'pricing.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Pricing</a>
            <a href="contact.php" class="block px-3 py-2 <?= $currentPage === 'contact.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="block px-3 py-2 <?= strpos($currentPage, 'dashboard.php') !== false ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Dashboard</a>
                <a href="logout.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Logout</a>
            <?php else: ?>
                <a href="login.php" class="block px-3 py-2 <?= $currentPage === 'login.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Login</a>
                <a href="signup.php" class="block px-3 py-2 <?= $currentPage === 'signup.php' ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>