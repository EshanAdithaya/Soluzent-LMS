<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['student_id']]);
                
                if (!empty($_POST['password'])) {
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_POST['student_id']]);
                }
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
                $stmt->execute([$_POST['student_id']]);
                break;
                
            case 'enroll':
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)");
                $stmt->execute([$_POST['student_id'], $_POST['class_id']]);
                break;
                
            case 'unenroll':
                $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND class_id = ?");
                $stmt->execute([$_POST['student_id'], $_POST['class_id']]);
                break;
        }
        header('Location: students.php');
        exit;
    }
}

// Fetch all students
$stmt = $pdo->query("
    SELECT u.*, 
           GROUP_CONCAT(c.name) as enrolled_classes,
           GROUP_CONCAT(c.id) as class_ids
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id
    LEFT JOIN classes c ON e.class_id = c.id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.name
");
$students = $stmt->fetchAll();

// Fetch all classes for enrollment
$stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name");
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-indigo-600">EduPortal Admin</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Dashboard</a>
                    <a href="classes.php" class="text-gray-500 hover:text-gray-700">Classes</a>
                    <a href="students.php" class="text-gray-900 border-b-2 border-indigo-500">Students</a>
                    <a href="materials.php" class="text-gray-500 hover:text-gray-700">Materials</a>
                    <button id="logoutBtn" class="text-gray-500 hover:text-gray-700">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Students Management</h2>
        </div>

        <!-- Students List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled Classes</th>
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
                            <?= $student['enrolled_classes'] ? htmlspecialchars($student['enrolled_classes']) : 'No classes' ?>
                        </td>
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
            <form id="editForm" method="POST">
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
                        <input type="password" name="password" id="editPassword"
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
                        <?php foreach ($classes as $class): ?>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <span><?= htmlspecialchars($class['name']) ?></span>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="enroll">
                                <input type="hidden" name="student_id" class="enroll-student-id">
                                <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                <button type="submit" class="text-green-600 hover:text-green-900">Enroll</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
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

    <script>
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
            const enrollStudentIds = document.getElementsByClassName('enroll-student-id');
            for (let input of enrollStudentIds) {
                input.value = studentId;
            }
            document.getElementById('enrollModal').classList.remove('hidden');
        }

        function closeEnrollModal() {
            document.getElementById('enrollModal').classList.add('hidden');
        }

        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student?')) {
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

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('../auth/logout.php');
                const data = await response.json();
                if (data.success) {
                    window.location.href = '../login.php';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>