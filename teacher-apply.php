<?php
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user already has a pending application
        $check = $pdo->prepare("SELECT id FROM teacher_profiles WHERE user_id = ?");
        $check->execute([$_SESSION['user_id']]);
        
        if ($check->rowCount() > 0) {
            $error = "You already have a teacher application submitted.";
        } else {
            // Insert the application
            $stmt = $pdo->prepare("
                INSERT INTO teacher_profiles (user_id, qualification, bio, expertise)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['qualification'],
                $_POST['bio'],
                $_POST['expertise']
            ]);

            // Redirect to confirmation page
            header('Location: teacher-apply.php?success=1');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "An error occurred while submitting your application.";
    }
}

// Check application status if already applied
$applicationStatus = null;
try {
    $stmt = $pdo->prepare("
        SELECT status, created_at 
        FROM teacher_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $applicationStatus = $stmt->fetch();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Application - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'navbar.php'; ?>

    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow px-6 py-8">
            <h1 class="text-2xl font-bold mb-6">Teacher Application</h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <p class="text-green-700">Your application has been submitted successfully!</p>
                </div>
            <?php endif; ?>

            <?php if ($applicationStatus): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <p class="text-blue-700">
                        Application Status: <strong><?= ucfirst($applicationStatus['status']) ?></strong><br>
                        Submitted on: <?= date('F j, Y', strtotime($applicationStatus['created_at'])) ?>
                    </p>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="qualification" class="block text-sm font-medium text-gray-700">
                            Educational Qualifications
                        </label>
                        <textarea
                            id="qualification"
                            name="qualification"
                            rows="3"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="List your degrees, certifications, and relevant qualifications"
                        ></textarea>
                    </div>

                    <div>
                        <label for="expertise" class="block text-sm font-medium text-gray-700">
                            Areas of Expertise
                        </label>
                        <input
                            type="text"
                            id="expertise"
                            name="expertise"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g., Mathematics, Physics, Computer Science"
                        >
                    </div>

                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700">
                            Professional Bio
                        </label>
                        <textarea
                            id="bio"
                            name="bio"
                            rows="5"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Tell us about your teaching experience and approach to education"
                        ></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Submit Application
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>