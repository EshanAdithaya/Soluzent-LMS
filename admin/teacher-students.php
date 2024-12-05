<?php
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'generate_invite':
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    UPDATE teacher_invite_links 
                    SET status = 'inactive' 
                    WHERE teacher_id = ? AND status = 'active'
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                $inviteCode = bin2hex(random_bytes(16));
                $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_invite_links 
                    (teacher_id, invite_code, expires_at, status) 
                    VALUES (?, ?, ?, 'active')
                ");
                $stmt->execute([$_SESSION['user_id'], $inviteCode, $expiryDate]);
                
                $pdo->commit();
                $_SESSION['invite_link'] = "https://plankton-app-us3aj.ondigitalocean.app/register-invite.php?invite=" . $inviteCode;
                $_SESSION['success'] = "New invite link generated successfully";
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Failed to generate invite link";
            }
            break;
            
        case 'enroll':
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO enrollments (student_id, class_id, teacher_id) 
                    SELECT ?, ?, ?
                    FROM teacher_students 
                    WHERE teacher_id = ? AND student_id = ? AND status = 'accepted'
                ");
                $stmt->execute([
                    $_POST['student_id'],
                    $_POST['class_id'],
                    $_SESSION['user_id'],
                    $_SESSION['user_id'],
                    $_POST['student_id']
                ]);
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
            }
            break;
    }
}

// Get active invite link
$stmt = $pdo->prepare("
    SELECT invite_code, expires_at, used_by 
    FROM teacher_invite_links 
    WHERE teacher_id = ? 
    AND expires_at > NOW()
    AND status = 'active'
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$activeInvite = $stmt->fetch();

// Get all invite links
$stmt = $pdo->prepare("
    SELECT invite_code, expires_at, used_by, status, created_at
    FROM teacher_invite_links 
    WHERE teacher_id = ? 
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$allInvites = $stmt->fetchAll();

// Get teacher's students
$stmt = $pdo->prepare("
    SELECT u.*, ts.status, u.last_access,
           COUNT(DISTINCT e.class_id) as enrolled_classes
    FROM users u
    JOIN teacher_students ts ON u.id = ts.student_id
    LEFT JOIN enrollments e ON u.id = e.student_id AND e.teacher_id = ts.teacher_id
    WHERE ts.teacher_id = ? AND ts.status = 'accepted'
    GROUP BY u.id
    ORDER BY u.name
");
$stmt->execute([$_SESSION['user_id']]);
$students = $stmt->fetchAll();

// Get teacher's classes
$stmt = $pdo->prepare("
    SELECT c.* 
    FROM classes c
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'admin-header.php';?>
    
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">My Students</h2>
            <button onclick="openInviteModal()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                Generate Invite Link
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Access</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrolled Classes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="px-6 py-4"><?= htmlspecialchars($student['name']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($student['email']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($student['phone']) ?></td>
                        <td class="px-6 py-4">
                            <?= $student['last_access'] ? date('Y-m-d H:i', strtotime($student['last_access'])) : 'Never' ?>
                        </td>
                        <td class="px-6 py-4"><?= $student['enrolled_classes'] ?></td>
                        <td class="px-6 py-4">
                            <button onclick="enrollInClass(<?= $student['id'] ?>)" 
                                    class="text-indigo-600 hover:text-indigo-900">
                                Enroll in Class
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invite Modal -->
    <div id="inviteModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Student Invite Links</h3>
            
            <?php if ($activeInvite): ?>
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Active Invite Link:</h4>
                <div class="p-3 bg-green-50 border border-green-200 rounded">
                    <div class="flex items-center justify-between">
                        <input type="text" 
                               value="https://plankton-app-us3aj.ondigitalocean.app/register-invite.php?invite=<?= htmlspecialchars($activeInvite['invite_code']) ?>" 
                               readonly
                               class="block w-full px-2 py-1 text-sm border border-gray-300 rounded bg-white">
                        <button onclick="copyLink(this)" 
                                class="ml-2 text-indigo-600 hover:text-indigo-900">Copy</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Expires: <?= date('Y-m-d H:i', strtotime($activeInvite['expires_at'])) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Previous Links:</h4>
                <div class="max-h-48 overflow-y-auto">
                    <?php foreach ($allInvites as $invite): ?>
                        <?php if ($invite['status'] === 'inactive'): ?>
                        <div class="mb-2 p-2 bg-gray-100 rounded text-sm">
                            <div class="flex items-center justify-between text-gray-500">
                                <span class="truncate"><?= substr($invite['invite_code'], 0, 16) ?>...</span>
                                <span>Created: <?= date('Y-m-d', strtotime($invite['created_at'])) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST" class="mb-4" onsubmit="return submitInviteForm(event)">
                <input type="hidden" name="action" value="generate_invite">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Generate New Link
                </button>
            </form>

            <button onclick="closeModal('inviteModal')" 
                    class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                Close
            </button>
        </div>
    </div>

    <!-- Enroll Modal -->
    <div id="enrollModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Enroll in Class</h3>
            <form method="POST">
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="student_id" id="enrollStudentId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Select Class</label>
                    <select name="class_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>">
                            <?= htmlspecialchars($class['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('enrollModal')"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">Cancel</button>
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Enroll</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function copyLink(button) {
            const input = button.parentElement.querySelector('input');
            input.select();
            document.execCommand('copy');
            button.textContent = 'Copied!';
            setTimeout(() => button.textContent = 'Copy', 2000);
        }

        function openInviteModal() {
            document.getElementById('inviteModal').classList.remove('hidden');
        }

        function enrollInClass(studentId) {
            document.getElementById('enrollStudentId').value = studentId;
            document.getElementById('enrollModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function submitInviteForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            });

            return false;
        }

        window.onclick = function(event) {
            ['inviteModal', 'enrollModal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    closeModal(modalId);
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                ['inviteModal', 'enrollModal'].forEach(modalId => {
                    closeModal(modalId);
                });
            }
        });
    </script>
</body>
</html>