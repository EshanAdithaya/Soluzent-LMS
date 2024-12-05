<?php
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check for existing application
        $check = $pdo->prepare("SELECT id FROM teacher_profiles WHERE user_id = ?");
        $check->execute([$_SESSION['user_id']]);
        
        if ($check->rowCount() > 0) {
            $error = "You already have a teacher application submitted.";
        } else {
            // Insert the application with required fields
            $stmt = $pdo->prepare("
                INSERT INTO teacher_profiles (
                    user_id, email, phone, qualification, expertise, bio,
                    address, city, state, postal_code,
                    teaching_certifications, linkedin_profile,
                    emergency_contact_phone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['qualification'],
                $_POST['expertise'],
                $_POST['bio'],
                $_POST['address'] ?? null,
                $_POST['city'] ?? null,
                $_POST['state'] ?? null,
                $_POST['postal_code'] ?? null,
                $_POST['teaching_certifications'] ?? null,
                $_POST['linkedin_profile'] ?? null,
                $_POST['emergency_contact_phone'] ?? null
            ]);

            header('Location: teacher-apply.php?success=1');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "An error occurred while submitting your application.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Application</title>
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

            <form method="POST" class="space-y-6">
                <!-- Required Fields -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 required">Email</label>
                        <input type="email" name="email" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 required">Phone Number</label>
                        <input type="tel" name="phone" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" required rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Educational Qualifications</label>
                    <textarea name="qualification" required rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="List your degrees, certifications, and relevant qualifications"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Areas of Expertise</label>
                    <textarea name="expertise" required rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="List your teaching subjects and specializations"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Professional Bio</label>
                    <textarea name="bio" required rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Tell us about your teaching experience and approach"></textarea>
                </div>

                <!-- Optional Fields -->
                <div class="border-t pt-6">
                    <h2 class="text-lg font-medium mb-4">Additional Information (Optional)</h2>
                    
                    <div class="space-y-6">
                        

                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" name="city"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State</label>
                                <input type="text" name="state"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                <input type="text" name="postal_code"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Teaching Certifications</label>
                            <textarea name="teaching_certifications" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="List any teaching certifications you hold"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">LinkedIn Profile</label>
                                <input type="url" name="linkedin_profile"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Emergency Contact Phone</label>
                                <input type="tel" name="emergency_contact_phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>