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
        // Public menu items
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
                    ['url' => 'profile.php', 'text' => 'Profile'],
                ]
            ]
        ];
    }

    public function isActive($page) {
        return $this->currentPage === $page;
    }

    public function renderLink($url, $text, $isButton = false) {
        $fullUrl = $this->getFullUrl($url);
        $isActive = $this->isActive(basename($url));
        $classes = $isButton 
            ? 'bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md'
            : ($isActive ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600');
        
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
<!-- Navigation -->
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
                    <?php foreach ($this->menuItems['auth'][$userRole] as $item): ?>
                        <?= $this->renderLink($item['url'], $item['text']) ?>
                    <?php endforeach; ?>
                    
                    <!-- Logout Form -->
                    <form action="<?= $this->getFullUrl('asset/php/logout.php') ?>" method="POST" class="inline">
                        <button type="submit" class="text-gray-600 hover:text-indigo-600">Logout</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button type="button" class="mobile-menu-button">
                    <i class="fas fa-bars text-gray-500 text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="hidden mobile-menu md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <?php if (!$isAuthenticated): ?>
                <?php foreach ($this->menuItems['public'] as $item): ?>
                    <?= $this->renderLink($item['url'], $item['text']) ?>
                <?php endforeach; ?>
                <?= $this->renderLink('login.php', 'Login') ?>
                <?= $this->renderLink('signup.php', 'Sign Up') ?>
            <?php else: ?>
                <?php foreach ($this->menuItems['auth'][$userRole] as $item): ?>
                    <?= $this->renderLink($item['url'], $item['text']) ?>
                <?php endforeach; ?>
                <form action="<?= $this->getFullUrl('asset/php/logout.php') ?>" method="POST">
                    <button type="submit" class="block w-full text-left px-3 py-2 text-gray-600 hover:text-indigo-600">
                        Logout
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Menu JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Close menu on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                mobileMenu.classList.add('hidden');
            }
        });
    }
});
</script>
<?php
    }
}

// Usage
$navigation = new Navigation();
$navigation->render();
?>