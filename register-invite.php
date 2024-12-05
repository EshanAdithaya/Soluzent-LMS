<?php
require_once 'asset/php/config.php';
require_once 'asset/php/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inviteCode = $_POST['invite_code'];
    $email = $_POST['email'];

    try {
        $pdo->beginTransaction();

        // Check if invite is valid
        $stmt = $pdo->prepare("
            SELECT til.*, t.name as teacher_name 
            FROM teacher_invite_links til
            JOIN users t ON til.teacher_id = t.id
            WHERE til.invite_code = ? 
            AND til.expires_at > NOW()
            AND til.used_by IS NULL
        ");
        $stmt->execute([$inviteCode]);
        $invite = $stmt->fetch();

        if (!$invite) {
            throw new Exception('Invalid or expired invite code');
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Existing user - connect to teacher
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO teacher_students (teacher_id, student_id, status) 
                VALUES (?, ?, 'accepted')
            ");
            $stmt->execute([$invite['teacher_id'], $user['id']]);

            // Mark invite as used
            $stmt = $pdo->prepare("UPDATE teacher_invite_links SET used_by = ? WHERE invite_code = ?");
            $stmt->execute([$user['id'], $inviteCode]);

            $_SESSION['success'] = "Successfully connected with teacher " . $invite['teacher_name'];
        } else {
            // Redirect to signup with invite code
            $_SESSION['invite_code'] = $inviteCode;
            header('Location: signup.php');
            exit;
        }

        $pdo->commit();
        header('Location: student/dashboard.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register with Invite</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Join Your Teacher's Class</h2>
                <p class="mt-2 text-sm text-gray-600">Enter your email to continue</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-8 space-y-6">
                <input type="hidden" name="invite_code" value="<?= htmlspecialchars($_GET['invite'] ?? '') ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" id="email" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>