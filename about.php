<?php
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';

// Get about page content
$stmt = $pdo->prepare("SELECT * FROM about_page WHERE id = 1");
$stmt->execute();
$about = $stmt->fetch(PDO::FETCH_ASSOC);

// Get active team members ordered by display order
$stmt = $pdo->prepare("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order");
$stmt->execute();
$team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($about['meta_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($about['meta_description']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once 'navbar.php'; ?>

    <!-- About Hero Section -->
    <div class="pt-16">
        <div class="relative bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl font-extrabold text-gray-900">
                        <?php echo htmlspecialchars($about['hero_title']); ?>
                    </h1>
                    <p class="mt-4 text-xl text-gray-500">
                        <?php echo htmlspecialchars($about['hero_subtitle']); ?>
                    </p>
                </div>

                <!-- Company Story -->
                <div class="mt-16">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                <?php echo htmlspecialchars($about['story_title']); ?>
                            </h2>
                            <div class="text-gray-600 space-y-4">
                                <?php echo nl2br(htmlspecialchars($about['story_content'])); ?>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <img src="/api/placeholder/500/300" alt="About Us" class="rounded-lg shadow-lg">
                        </div>
                    </div>
                </div>

                <!-- Mission & Vision -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <div class="text-indigo-600 mb-4">
                            <i class="fas fa-bullseye text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Our Mission</h3>
                        <p class="text-gray-600">
                            <?php echo nl2br(htmlspecialchars($about['mission_content'])); ?>
                        </p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <div class="text-indigo-600 mb-4">
                            <i class="fas fa-eye text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Our Vision</h3>
                        <p class="text-gray-600">
                            <?php echo nl2br(htmlspecialchars($about['vision_content'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Team Section -->
                <div class="mt-16">
                    <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Our Leadership Team</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <?php foreach ($team_members as $member): ?>
                        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                            <?php if (!empty($member['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($member['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                     class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user text-gray-400 text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($member['position']); ?></p>
                            <?php if (!empty($member['bio'])): ?>
                                <p class="mt-2 text-sm text-gray-500"><?php echo htmlspecialchars($member['bio']); ?></p>
                            <?php endif; ?>
                            <div class="mt-4 flex justify-center space-x-4">
                                <?php if (!empty($member['social_linkedin'])): ?>
                                    <a href="<?php echo htmlspecialchars($member['social_linkedin']); ?>" 
                                       target="_blank" 
                                       class="text-gray-400 hover:text-blue-500">
                                        <i class="fab fa-linkedin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($member['social_twitter'])): ?>
                                    <a href="<?php echo htmlspecialchars($member['social_twitter']); ?>" 
                                       target="_blank" 
                                       class="text-gray-400 hover:text-blue-400">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>
</body>
</html>