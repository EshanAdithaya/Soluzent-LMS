<?php
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
include_once 'admin-header.php';
require_once 'adminSession.php';

// Get about page content
$stmt = $pdo->prepare("SELECT * FROM about_page WHERE id = 1");
$stmt->execute();
$about = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Update about page content
        $stmt = $pdo->prepare("
            UPDATE about_page SET 
                hero_title = ?,
                hero_subtitle = ?,
                story_title = ?,
                story_content = ?,
                mission_content = ?,
                vision_content = ?,
                meta_title = ?,
                meta_description = ?,
                updated_by = ?
            WHERE id = 1
        ");
        
        $stmt->execute([
            $_POST['hero_title'],
            $_POST['hero_subtitle'],
            $_POST['story_title'],
            $_POST['story_content'],
            $_POST['mission_content'],
            $_POST['vision_content'],
            $_POST['meta_title'],
            $_POST['meta_description'],
            $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        $success_message = "About page updated successfully!";
        
        // Refresh page content
        $stmt = $pdo->prepare("SELECT * FROM about_page WHERE id = 1");
        $stmt->execute();
        $about = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        $error_message = "Error updating content: " . $e->getMessage();
    }
}

// Get team members
$stmt = $pdo->prepare("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order");
$stmt->execute();
$team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Page - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Manage About Page</h1>
                <a href="../about.php" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                    View Page
                </a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-6">
                <!-- SEO Section -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">SEO Settings</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                            <input type="text" name="meta_title" value="<?php echo htmlspecialchars($about['meta_title'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                            <textarea name="meta_description" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo htmlspecialchars($about['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Hero Section</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="hero_title" value="<?php echo htmlspecialchars($about['hero_title'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subtitle</label>
                        <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($about['hero_subtitle'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Story Section -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Our Story</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Story Title</label>
                        <input type="text" name="story_title" value="<?php echo htmlspecialchars($about['story_title'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Story Content</label>
                        <textarea name="story_content" rows="6"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo htmlspecialchars($about['story_content'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Mission & Vision -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Mission & Vision</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mission</label>
                        <textarea name="mission_content" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo htmlspecialchars($about['mission_content'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vision</label>
                        <textarea name="vision_content" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo htmlspecialchars($about['vision_content'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Changes
                    </button>
                </div>
            </form>

            <!-- Team Members Section -->
            <div class="mt-8 bg-white shadow-md rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Team Members</h2>
                    <a href="admin-team_members.php" class="text-indigo-600 hover:text-indigo-800">
                        Manage Team
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($team_members as $member): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center space-x-4">
                                <?php if (!empty($member['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($member['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($member['name']); ?>"
                                         class="w-16 h-16 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-gray-400 text-2xl">👤</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($member['name']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['position']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>