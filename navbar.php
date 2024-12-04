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
                    <a href="index.php" class="text-gray-600 hover:text-indigo-600">Home</a>
                    <a href="about.php" class="text-gray-600 hover:text-indigo-600">About Us</a>
                    <a href="features.php" class="text-gray-600 hover:text-indigo-600">Features</a>
                    <a href="pricing.php" class="text-gray-600 hover:text-indigo-600">Pricing</a>
                    <a href="contact.php" class="text-gray-600 hover:text-indigo-600">Contact</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php' ?>" 
                           class="text-gray-600 hover:text-indigo-600">Dashboard</a>
                        <a href="asset/php/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-indigo-600">Login</a>
                        <a href="signup.php" 
                           class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
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
                <a href="index.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Home</a>
                <a href="about.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">About Us</a>
                <a href="features.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Features</a>
                <a href="pricing.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Pricing</a>
                <a href="contact.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Contact</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Dashboard</a>
                    <a href="logout.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Login</a>
                    <a href="signup.php" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
