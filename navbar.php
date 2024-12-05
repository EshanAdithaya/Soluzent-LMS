<?php
session_start();

class Navigation {
    private $currentPage;
    private $baseUrl;
    private $menuItems;

    public function __construct() {
        $this->currentPage = basename($_SERVER['PHP_SELF']);
        $this->baseUrl = dirname($_SERVER['PHP_SELF']);
        $this->setupMenuItems();
    }

    private function setupMenuItems() {
        $this->menuItems = [
            'public' => [
                ['url' => 'index.php', 'text' => 'Home'],
                ['url' => 'about.php', 'text' => 'About Us'],
                ['url' => 'features.php', 'text' => 'Features'],
                ['url' => 'pricing.php', 'text' => 'Pricing'],
                ['url' => 'contact.php', 'text' => 'Contact'],
            ],
            'auth' => [
                'student' => [
                    ['url' => 'student/dashboard.php', 'text' => 'Dashboard'],
                    ['url' => 'profile.php', 'text' => 'Profile'],
                ],
                'admin' => [
                    ['url' => 'admin/dashboard.php', 'text' => 'Dashboard'],
                    // ['url' => 'admin/teacher-applications.php', 'text' => 'Teacher Applications'],
                    ['url' => 'admin/classes.php', 'text' => 'Classes'],
                    ['url' => 'admin/materials.php', 'text' => 'Materials'],
                    ['url' => 'profile.php', 'text' => 'Profile'],
                ],
                'teacher' => [
                    ['url' => 'admin/dashboard.php', 'text' => 'Dashboard'],
                    ['url' => 'admin/classes.php', 'text' => 'Classes'],
                    ['url' => 'admin/materials.php', 'text' => 'Materials'],
                    ['url' => 'profile.php', 'text' => 'Profile'],
                ]
            ]
        ];
    }

    public function isActive($page) {
        return $this->currentPage === $page;
    }

    public function renderLink($url, $text, $isButton = false, $isMobile = false) {
        $fullUrl = $this->getFullUrl($url);
        $isActive = $this->isActive(basename($url));
        
        if ($isMobile) {
            $classes = $isActive 
                ? 'block px-3 py-2 text-base font-medium text-indigo-600'
                : 'block px-3 py-2 text-base font-medium text-gray-600 hover:text-indigo-600';
        } else {
            $classes = $isButton 
                ? 'bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium'
                : ($isActive ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600');
        }
        
        return "<a href=\"{$fullUrl}\" class=\"{$classes}\">{$text}</a>";
    }

    private function getFullUrl($url) {
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    public function render() {
        $isAuthenticated = isset($_SESSION['user_id']) && isset($_SESSION['role']);
        $userRole = $isAuthenticated ? $_SESSION['role'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Navigation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <nav class="bg-white shadow-lg fixed w-full z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?= $this->getFullUrl('index.php') ?>" class="text-2xl font-bold text-indigo-600">
                            SOLUZENT LMS
                        </a>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <?php if (!$isAuthenticated): ?>
                        <!-- Public Navigation -->
                        <?php foreach ($this->menuItems['public'] as $item): ?>
                            <?= $this->renderLink($item['url'], $item['text']) ?>
                        <?php endforeach; ?>
                        
                        <!-- Auth Links -->
                        <?= $this->renderLink('login.php', 'Login') ?>
                        <?= $this->renderLink('signup.php', 'Sign Up', true) ?>
                    <?php else: ?>
                        <!-- Authenticated Navigation -->
                        <?php if (isset($this->menuItems['auth'][$userRole])): ?>
                            <?php foreach ($this->menuItems['auth'][$userRole] as $item): ?>
                                <?= $this->renderLink($item['url'], $item['text']) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- User Role Badge -->
                        <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800 capitalize">
                            <?= htmlspecialchars($_SESSION['name'] ?? '') ?> (<?= ucfirst($userRole) ?>)
                        </span>
                        
                        <!-- Logout Form -->
                        <form action="<?= $this->getFullUrl('asset/php/logout.php') ?>" method="POST" class="inline">
                            <button type="submit" class="text-gray-600 hover:text-indigo-600">Logout</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button type="button" id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-b border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <?php if (!$isAuthenticated): ?>
                    <?php foreach ($this->menuItems['public'] as $item): ?>
                        <?= $this->renderLink($item['url'], $item['text'], false, true) ?>
                    <?php endforeach; ?>
                    <?= $this->renderLink('login.php', 'Login', false, true) ?>
                    <div class="px-3 py-2">
                        <?= $this->renderLink('signup.php', 'Sign Up', true, true) ?>
                    </div>
                <?php else: ?>
                    <?php if (isset($this->menuItems['auth'][$userRole])): ?>
                        <?php foreach ($this->menuItems['auth'][$userRole] as $item): ?>
                            <?= $this->renderLink($item['url'], $item['text'], false, true) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- User Role Badge -->
                    <div class="px-3 py-2">
                        <span class="px-2 py-1 text-sm rounded-full bg-gray-100 text-gray-800 capitalize">
                            <?= htmlspecialchars($_SESSION['name'] ?? '') ?> (<?= ucfirst($userRole) ?>)
                        </span>
                    </div>
                    
                    <form action="<?= $this->getFullUrl('asset/php/logout.php') ?>" method="POST">
                        <button type="submit" class="block w-full text-left px-3 py-2 text-base font-medium text-gray-600 hover:text-indigo-600">
                            Logout
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        if (mobileMenuButton && mobileMenu) {
            let isOpen = false;

            const toggleMenu = () => {
                isOpen = !isOpen;
                mobileMenu.classList.toggle('hidden');
                mobileMenuButton.setAttribute('aria-expanded', isOpen.toString());
            };

            mobileMenuButton.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleMenu();
            });

            // Close menu when clicking outside
            document.addEventListener('click', (event) => {
                if (isOpen && !mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                    toggleMenu();
                }
            });

            // Close menu on ESC key
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && isOpen) {
                    toggleMenu();
                }
            });

            // Prevent clicks inside mobile menu from closing it
            mobileMenu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    });
    </script>
</body>
</html>
<?php
    }
}

// Usage
$navigation = new Navigation();
$navigation->render();
?>