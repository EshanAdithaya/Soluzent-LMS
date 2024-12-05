<?php
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: login.php');
    exit;
}

$isTeacher = $_SESSION['role'] === 'teacher';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'update':
                    // Validate input
                    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
                        throw new Exception('All fields are required except password');
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // For teachers, verify they can modify this student
                    if ($isTeacher) {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM teacher_students 
                            WHERE teacher_id = ? AND student_id = ? AND status = 'accepted'
                        ");
                        $stmt->execute([$_SESSION['user_id'], $_POST['student_id']]);
                        if ($stmt->fetchColumn() == 0) {
                            throw new Exception('You do not have permission to modify this student');
                        }
                    }
                    
                    // Update basic info
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'student'");
                    $result = $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['student_id']]);
                    
                    if (!$result) {
                        throw new Exception('Failed to update student information');
                    }
                    
                    // Update password if provided
                    if (!empty($_POST['password'])) {
                        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'student'");
                        $result = $stmt->execute([$hashedPassword, $_POST['student_id']]);
                        
                        if (!$result) {
                            throw new Exception('Failed to update password');
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['success'] = 'Student updated successfully';
                    break;
                    
                case 'delete':
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // For teachers, verify they can delete this student
                    if ($isTeacher) {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM teacher_students 
                            WHERE teacher_id = ? AND student_id = ? AND status = 'accepted'
                        ");
                        $stmt->execute([$_SESSION['user_id'], $_POST['student_id']]);
                        if ($stmt->fetchColumn() == 0) {
                            throw new Exception('You do not have permission to delete this student');
                        }
                    }
                    
                    // Delete enrollments first
                    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?");
                    $stmt->execute([$_POST['student_id']]);
                    
                    // Delete from teacher_students
                    $stmt = $pdo->prepare("DELETE FROM teacher_students WHERE student_id = ?");
                    $stmt->execute([$_POST['student_id']]);
                    
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
                    $result = $stmt->execute([$_POST['student_id']]);
                    
                    if (!$result) {
                        throw new Exception('Failed to delete student');
                    }
                    
                    $pdo->commit();
                    $_SESSION['success'] = 'Student deleted successfully';
                    break;
                    
                case 'enroll':
                    // Verify teacher has access to student
                    if ($isTeacher) {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM teacher_students 
                            WHERE teacher_id = ? AND student_id = ? AND status = 'accepted'
                        ");
                        $stmt->execute([$_SESSION['user_id'], $_POST['student_id']]);
                        if ($stmt->fetchColumn() == 0) {
                            throw new Exception('You do not have permission to enroll this student');
                        }
                    }
                    
                    // Check if already enrolled
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM enrollments 
                        WHERE student_id = ? AND class_id = ?
                    ");
                    $stmt->execute([$_POST['student_id'], $_POST['class_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Student is already enrolled in this class');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO enrollments (student_id, class_id, teacher_id) 
                        VALUES (?, ?, ?)
                    ");
                    $result = $stmt->execute([
                        $_POST['student_id'], 
                        $_POST['class_id'], 
                        $isTeacher ? $_SESSION['user_id'] : null
                    ]);
                    
                    if (!$result) {
                        throw new Exception('Failed to enroll student');
                    }
                    
                    $_SESSION['success'] = 'Student enrolled successfully';
                    break;
                    
                case 'unenroll':
                    if ($isTeacher) {
                        $stmt = $pdo->prepare("
                            DELETE FROM enrollments 
                            WHERE student_id = ? AND class_id = ? AND teacher_id = ?
                        ");
                        $result = $stmt->execute([
                            $_POST['student_id'], 
                            $_POST['class_id'], 
                            $_SESSION['user_id']
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            DELETE FROM enrollments 
                            WHERE student_id = ? AND class_id = ?
                        ");
                        $result = $stmt->execute([
                            $_POST['student_id'], 
                            $_POST['class_id']
                        ]);
                    }
                    
                    if (!$result) {
                        throw new Exception('Failed to unenroll student');
                    }
                    
                    $_SESSION['success'] = 'Student unenrolled successfully';
                    break;

                case 'generate_invite':
                    if (!$isTeacher) {
                        throw new Exception('Only teachers can generate invite links');
                    }
                    
                    $pdo->beginTransaction();
                    
                    // Deactivate existing active links
                    $stmt = $pdo->prepare("
                        UPDATE teacher_invite_links 
                        SET status = 'inactive' 
                        WHERE teacher_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    
                    // Generate new invite link
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
                    break;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: students.php');
        exit;
    }
}

// Handle AJAX requests for available classes
if (isset($_GET['action']) && $_GET['action'] === 'get_available_classes') {
    try {
        if ($isTeacher) {
            $stmt = $pdo->prepare("
                SELECT c.id, c.name 
                FROM classes c
                JOIN teacher_classes tc ON c.id = tc.class_id
                WHERE tc.teacher_id = ?
                AND c.id NOT IN (
                    SELECT class_id 
                    FROM enrollments 
                    WHERE student_id = ?
                )
                ORDER BY c.name
            ");
            $stmt->execute([$_SESSION['user_id'], $_GET['student_id']]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.id, c.name 
                FROM classes c
                WHERE c.id NOT IN (
                    SELECT class_id 
                    FROM enrollments 
                    WHERE student_id = ?
                )
                ORDER BY c.name
            ");
            $stmt->execute([$_GET['student_id']]);
        }
        
        $availableClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'classes' => $availableClasses]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch available classes']);
        exit;
    }
}

// Get active invite link for teachers
$activeInvite = null;
$allInvites = [];
if ($isTeacher) {
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

    $stmt = $pdo->prepare("
        SELECT invite_code, expires_at, used_by, status, created_at
        FROM teacher_invite_links 
        WHERE teacher_id = ? 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $allInvites = $stmt->fetchAll();
}

// Handle search query
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

// Get students based on role
if ($isTeacher) {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name ASC SEPARATOR ', ') as enrolled_classes,
            GROUP_CONCAT(DISTINCT c.id ORDER BY c.name ASC SEPARATOR ',') as class_ids,
            COUNT(DISTINCT e.class_id) as enrolled_class_count,
            MAX(u.last_access) as last_access
        FROM users u
        JOIN teacher_students ts ON u.id = ts.student_id AND ts.teacher_id = ? AND ts.status = 'accepted'
        LEFT JOIN enrollments e ON u.id = e.student_id AND e.teacher_id = ts.teacher_id
        LEFT JOIN classes c ON e.class_id = c.id
        WHERE u.role = 'student' AND u.name LIKE ?
        GROUP BY u.id
        ORDER BY u.name
    ");
    $stmt->execute([$_SESSION['user_id'], '%' . $searchQuery . '%']);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name ASC SEPARATOR ', ') as enrolled_classes,
            GROUP_CONCAT(DISTINCT c.id ORDER BY c.name ASC SEPARATOR ',') as class_ids,
            COUNT(DISTINCT e.class_id) as enrolled_class_count,
            MAX(u.last_access) as last_access,
            GROUP_CONCAT(DISTINCT CONCAT(t.name) ORDER BY t.name ASC SEPARATOR ', ') as registered_teachers
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        LEFT JOIN classes c ON e.class_id = c.id
        LEFT JOIN teacher_students ts ON u.id = ts.student_id
        LEFT JOIN users t ON ts.teacher_id = t.id AND t.role = 'teacher' AND ts.status = 'accepted'
        WHERE u.role = 'student' AND u.name LIKE ?
        GROUP BY u.id
        ORDER BY u.name
    ");
    $stmt->execute(['%' . $searchQuery . '%']);
}
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch classes for enrollment
if ($isTeacher) {
    $stmt = $pdo->prepare("
        SELECT c.* 
        FROM classes c
        JOIN teacher_classes tc ON c.id = tc.class_id
        WHERE tc.teacher_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("SELECT * FROM classes ORDER BY name");
}
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title based on role
$title = $isTeacher ? 'My Students' : 'Students Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include_once 'admin-header.php';?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900"><?= $title ?></h2>
            <div class="flex space-x-4">
            <form method="GET" class="flex space-x-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" 
                           placeholder="Search students..." 
                           class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Search
                    </button>
                </form>
                <?php if ($isTeacher): ?>
                <button onclick="openInviteModal()" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Generate Invite Link
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Students List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Access</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled Classes</th>
                        <?php if (!$isTeacher): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered Teachers</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($student['name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($student['email']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($student['phone']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $student['last_access'] ? date('Y-m-d H:i', strtotime($student['last_access'])) : 'Never' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $student['enrolled_classes'] ? htmlspecialchars($student['enrolled_classes']) : 'No classes' ?>
                        </td>
                        <?php if (!$isTeacher): ?>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $student['registered_teachers'] ? htmlspecialchars($student['registered_teachers']) : 'No teachers' ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($student)) ?>)" 
                                    class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button onclick="openEnrollModal(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name']) ?>')" 
                                    class="ml-4 text-green-600 hover:text-green-900">Enroll</button>
                            <button onclick="confirmDelete(<?= $student['id'] ?>)" 
                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form id="editForm" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="student_id" id="editStudentId">
                
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Student</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="editName" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="editEmail" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" id="editPhone" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="editPassword" minlength="6"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Enroll Modal -->
    <div id="enrollModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Enroll Student: <span id="enrollStudentName"></span></h3>
            
            <div class="mt-4 space-y-4">
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-700">Available Classes</h4>
                    <div id="availableClasses" class="space-y-2">
                        <!-- Dynamically populated -->
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button onclick="closeEnrollModal()"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                    Close
                </button>
            </div>
        </div>
    </div>

    <?php if ($isTeacher): ?>
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
                <button type="submit" 
                        class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Generate New Link
                </button>
            </form>

            <button onclick="closeModal('inviteModal')" 
                    class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                Close
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function validateForm() {
            const password = document.getElementById('editPassword').value;
            if (password && password.length < 6) {
                alert('Password must be at least 6 characters long');
                return false;
            }
            return true;
        }

        function openEditModal(student) {
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editName').value = student.name;
            document.getElementById('editEmail').value = student.email;
            document.getElementById('editPhone').value = student.phone;
            document.getElementById('editPassword').value = '';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openEnrollModal(studentId, studentName) {
            document.getElementById('enrollStudentName').textContent = studentName;
            document.getElementById('enrollModal').classList.remove('hidden');
            updateAvailableClasses(studentId);
        }

        function closeEnrollModal() {
            document.getElementById('enrollModal').classList.add('hidden');
        }

        function openInviteModal() {
            document.getElementById('inviteModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function copyLink(button) {
            const input = button.parentElement.querySelector('input');
            input.select();
            document.execCommand('copy');
            button.textContent = 'Copied!';
            setTimeout(() => button.textContent = 'Copy', 2000);
        }

        async function updateAvailableClasses(studentId) {
            try {
                const response = await fetch(`?action=get_available_classes&student_id=${studentId}`);
                if (!response.ok) throw new Error('Failed to fetch available classes');
                
                const data = await response.json();
                
                const container = document.getElementById('availableClasses');
                container.innerHTML = data.classes.map(c => `
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span>${c.name}</span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="enroll">
                            <input type="hidden" name="student_id" value="${studentId}">
                            <input type="hidden" name="class_id" value="${c.id}">
                            <button type="submit" class="text-green-600 hover:text-green-900">
                                Enroll
                            </button>
                        </form>
                    </div>
                `).join('') || '<p class="text-gray-500 p-2">No available classes</p>';
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to fetch available classes');
            }
        }

        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone and will remove the student from all enrolled classes.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="student_id" value="${studentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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

        // Close modals when clicking outside
        window.onclick = function(event) {
            ['editModal', 'enrollModal', 'inviteModal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    closeModal(modalId);
                }
            });
        }

        // Handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                ['editModal', 'enrollModal', 'inviteModal'].forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        closeModal(modalId);
                    }
                });
            }
        });

        // Add form validation listeners
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const name = document.getElementById('editName').value.trim();
            const email = document.getElementById('editEmail').value.trim();
            const phone = document.getElementById('editPhone').value.trim();
            
            if (!name || !email || !phone) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
            
            if (!phone.match(/^\+?[\d\s-]{10,}$/)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return;
            }
        });
    </script>
</body>
</html>