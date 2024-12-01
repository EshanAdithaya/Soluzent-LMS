<?php
session_start();
// require_once '../includes/config.php';
// require_once '../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO classes (name, description) VALUES (?, ?)");
                $stmt->execute([$_POST['name'], $_POST['description']]);
                break;
            case 'update':
                $stmt = $pdo->prepare("UPDATE classes SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $_POST['description'], $_POST['class_id']]);
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
                $stmt->execute([$_POST['class_id']]);
                break;
        }
        header('Location: classes.php');
        exit;
    }
}

// Fetch all classes
$stmt = $pdo->query("SELECT * FROM classes ORDER BY name");
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Management - Admin Dashboard</title>
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
                    <a href="classes.php" class="text-gray-900 border-b-2 border-indigo-500">Classes</a>
                    <a href="students.php" class="text-gray-500 hover:text-gray-700">Students</a>
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
            <h2 class="text-2xl font-bold text-gray-900">Classes Management</h2>
            <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add New Class
            </button>
        </div>

        <!-- Classes List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($classes as $class): 
                        // Get student count for this class
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE class_id = ?");
                        $stmt->execute([$class['id']]);
                        $studentCount = $stmt->fetchColumn();
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($class['name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($class['description']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $studentCount ?> students
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($class)) ?>)" 
                                    class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button onclick="confirmDelete(<?= $class['id'] ?>)" 
                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="classModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form id="classForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="class_id" id="classId">
                
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Class</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Class Name</label>
                        <input type="text" name="name" id="className" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="classDescription" rows="3" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Class';
            document.getElementById('formAction').value = 'create';
            document.getElementById('classId').value = '';
            document.getElementById('className').value = '';
            document.getElementById('classDescription').value = '';
            document.getElementById('classModal').classList.remove('hidden');
        }

        function openEditModal(classData) {
            document.getElementById('modalTitle').textContent = 'Edit Class';
            document.getElementById('formAction').value = 'update';
            document.getElementById('classId').value = classData.id;
            document.getElementById('className').value = classData.name;
            document.getElementById('classDescription').value = classData.description;
            document.getElementById('classModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('classModal').classList.add('hidden');
        }

        function confirmDelete(classId) {
            if (confirm('Are you sure you want to delete this class?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="class_id" value="${classId}">
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