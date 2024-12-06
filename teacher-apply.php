<?php
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';

// Add reCAPTCHA verification function
function verifyRecaptcha($recaptchaResponse) {
    $secretKey = "6LfS-pMqAAAAABSp6DEft8G35rthZ6UeTlqSbbO1"; // Replace with your secret key
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify reCAPTCHA first
        if (!isset($_POST['g-recaptcha-response']) || !verifyRecaptcha($_POST['g-recaptcha-response'])) {
            $error = "Please complete the reCAPTCHA verification.";
        } else {
            // Check if email already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$_POST['email']]);
            
            if ($check->rowCount() > 0) {
                $error = "Email already exists. Please use a different email or login if you already have an account.";
            } else {
                $pdo->beginTransaction();

                // First create the user account
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, role)
                    VALUES (?, ?, ?, ?, 'student')
                ");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $hashedPassword
                ]);
                
                $userId = $pdo->lastInsertId();

                // Then create the teacher profile
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_profiles (
                        user_id, email, phone, qualification, expertise, bio,
                        address, city, state, postal_code,
                        teaching_certifications, linkedin_profile,
                        emergency_contact_phone, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                $stmt->execute([
                    $userId,
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

                $pdo->commit();

                // Log the user in
                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = 'student';
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['email'] = $_POST['email'];

                header('Location: teacher-apply.php?success=1');
                exit;
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                    <p class="text-green-700">Your application has been submitted successfully! You can now login with your email and password.</p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" onsubmit="return validateForm()">
                <!-- Account Information -->
                <div class="border-b pb-6">
                    <h2 class="text-lg font-medium mb-4">Account Information</h2>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 required">Full Name</label>
                            <input type="text" name="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 required">Email</label>
                            <input type="email" name="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 required">Password</label>
                            <input type="password" name="password" id="password" required minlength="6"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 required">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 required">Phone Number</label>
                        <input type="tel" name="phone" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Required Teaching Information -->
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

                <!-- Optional Information -->
                <div class="border-t pt-6">
                    <h2 class="text-lg font-medium mb-4">Additional Information (Optional)</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

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

                <!-- Add reCAPTCHA before submit button -->
                <div class="mt-6 flex justify-center">
                    <div class="g-recaptcha" data-sitekey="6LfS-pMqAAAAABIZAGYVYyCZf2UwDbtehvEsYYti"></div>
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

    <script>
    function validateForm() {
        const password = document.getElementById('password').value;
        const confirm_password = document.getElementById('confirm_password').value;
        const recaptchaResponse = grecaptcha.getResponse();

        if (password !== confirm_password) {
            alert("Passwords do not match!");
            return false;
        }

        if (password.length < 6) {
            alert("Password must be at least 6 characters long!");
            return false;
        }

        if (!recaptchaResponse) {
            alert("Please complete the reCAPTCHA verification!");
            return false;
        }

        return true;
    }
    </script>
</body>
</html>