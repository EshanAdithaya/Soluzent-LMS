<?php
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
session_start();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO classes (name, description, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['description'], $_SESSION['user_id']]);
                $classId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_id, is_owner, can_modify) VALUES (?, ?, true, true)");
                $stmt->execute([$_SESSION['user_id'], $classId]);
                
                $pdo->commit();
                header('Location: classes.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Error creating class";
                header('Location: classes.php');
                exit;
            }
            break;

        case 'update':
            $stmt = $pdo->prepare("UPDATE classes SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['class_id']]);
            header('Location: classes.php');
            exit;
            break;

        case 'delete':
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
                $stmt->execute([$_POST['class_id']]);
                $pdo->commit();
                $_SESSION['success'] = "Class deleted successfully.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Error deleting class.";
            }
            header('Location: classes.php');
            exit;
            break;

        case 'enroll':
            if (isset($_POST['students']) && is_array($_POST['students'])) {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("
                        INSERT IGNORE INTO enrollments (student_id, class_id, teacher_id) 
                        SELECT ?, ?, ? 
                        FROM teacher_students 
                        WHERE teacher_id = ? AND student_id = ? AND status = 'accepted'
                    ");
                    
                    foreach ($_POST['students'] as $studentId) {
                        $stmt->execute([
                            $studentId, 
                            $_POST['class_id'],
                            $_SESSION['user_id'],
                            $_SESSION['user_id'],
                            $studentId
                        ]);
                    }
                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log($e->getMessage());
                }
            }
            header('Location: classes.php');
            exit;
            break;

        case 'unenroll':
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND class_id = ? AND teacher_id = ?");
            $stmt->execute([$_POST['student_id'], $_POST['class_id'], $_SESSION['user_id']]);
            echo json_encode(['success' => true]);
            exit;
            break;
    }
}

// Handle AJAX requests for viewing enrolled students
if (isset($_GET['action']) && $_GET['action'] === 'get_enrolled_students') {
    $classId = $_GET['class_id'];
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.last_access 
            FROM users u 
            JOIN enrollments e ON u.id = e.student_id 
            WHERE e.class_id = ? AND e.teacher_id = ?
        ");
        $stmt->execute([$classId, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.last_access,
                   t.name as teacher_name
            FROM users u 
            JOIN enrollments e ON u.id = e.student_id 
            JOIN users t ON e.teacher_id = t.id
            WHERE e.class_id = ?
        ");
        $stmt->execute([$classId]);
    }
    $students = $stmt->fetchAll();
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}

// Fetch classes based on role
if ($_SESSION['role'] === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(DISTINCT e.student_id) as student_count 
        FROM classes c 
        LEFT JOIN enrollments e ON c.id = e.class_id AND e.teacher_id = ?
        JOIN teacher_classes tc ON c.id = tc.class_id 
        WHERE tc.teacher_id = ?
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $classes = $stmt->fetchAll();

    // Fetch only connected students
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email 
        FROM users u 
        JOIN teacher_students ts ON u.id = ts.student_id
        WHERE ts.teacher_id = ? AND ts.status = 'accepted'
        ORDER BY u.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(DISTINCT e.student_id) as student_count,
               u.name as creator_name, u.email as creator_email 
        FROM classes c 
        LEFT JOIN enrollments e ON c.id = e.class_id 
        LEFT JOIN users u ON c.created_by = u.id
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $classes = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name");
}
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
   <?php include_once 'admin-header.php';?>

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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php if ($_SESSION['role'] !== 'teacher'): ?>Created By<?php endif; ?>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($classes as $class): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($class['name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php if ($_SESSION['role'] !== 'teacher'): ?>
                                <?= htmlspecialchars($class['creator_name']) ?> 
                                (<?= htmlspecialchars($class['creator_email']) ?>)
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($class['description']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <button onclick="viewStudents(<?= $class['id'] ?>)" class="text-indigo-600 hover:text-indigo-900">
                                <?= $class['student_count'] ?> students
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEnrollModal(<?= $class['id'] ?>)" 
                                    class="text-green-600 hover:text-green-900">Enroll</button>
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($class)) ?>)" 
                                    class="ml-4 text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button onclick="confirmDelete(<?= $class['id'] ?>)" 
                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Class Modal -->
    <div id="classModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form method="POST">
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
                    <button type="button" onclick="closeModal('classModal')"
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

    <!-- Enroll Students Modal -->
    <div id="enrollModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Enroll Students</h3>
            <form method="POST">
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="class_id" id="enrollClassId">
                
                <div class="space-y-4">
                    <input type="text" id="studentSearch" placeholder="Search students..." 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <div id="studentList" class="max-h-96 overflow-y-auto space-y-4">
                        <?php foreach ($students as $student): ?>
                        <div class="flex items-center student-item">
                            <input type="checkbox" name="students[]" value="<?= $student['id'] ?>"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label class="ml-3 text-sm text-gray-700">
                                <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['email']) ?>)
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('enrollModal')"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Enroll Selected Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Students Modal -->
    <div id="viewStudentsModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Enrolled Students</h3>
            <div id="enrolledStudentsList" class="space-y-2 max-h-96 overflow-y-auto">
                <!-- Students will be loaded here -->
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeModal('viewStudentsModal')"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                    Close
                </button>
            </div>
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

        function openEnrollModal(classId) {
            document.getElementById('enrollClassId').value = classId;
            document.getElementById('enrollModal').classList.remove('hidden');
        }

        async function viewStudents(classId) {
            try {
                const response = await fetch(`?action=get_enrolled_students&class_id=${classId}`);
                const data = await response.json();
                
                const studentsList = document.getElementById('enrolledStudentsList');
                studentsList.innerHTML = data.students.map(student => `
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span>${student.name} (${student.email})</span>
                        <button onclick="unenrollStudent(${classId}, ${student.id})" 
                                class="text-red-600 hover:text-red-900 text-sm">
                            Remove
                        </button>
                    </div>
                `).join('') || '<p class="text-gray-500">No students enrolled</p>';
                
                document.getElementById('viewStudentsModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
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

        async function unenrollStudent(classId, studentId) {
            if (confirm('Are you sure you want to remove this student from the class?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'unenroll');
                    formData.append('class_id', classId);
                    formData.append('student_id', studentId);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Refresh the students list
                viewStudents(classId);

                // Refresh the page to update student counts
                location.reload();
            }
            } catch (error) {
            console.error('Error:', error);
            alert('Error removing student from class');
            }
            }
            }

            // Close modals when clicking outside
            window.onclick = function(event) {
            const modals = ['classModal', 'enrollModal', 'viewStudentsModal'];
            modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
            closeModal(modalId);
            }
            });
            }

            // Handle escape key to close modals
            document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
            const modals = ['classModal', 'enrollModal', 'viewStudentsModal'];
            modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (!modal.classList.contains('hidden')) {
                closeModal(modalId);
            }
            });
            }
            });

            // Prevent form submission if no students selected in enroll modal
            document.querySelector('#enrollModal form').addEventListener('submit', function(e) {
            const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
            if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one student to enroll');
            }
            });

            // Search functionality for students in enroll modal
            document.getElementById('studentSearch').addEventListener('input', function() {
                const searchValue = this.value.toLowerCase();
                const studentItems = document.querySelectorAll('#studentList .student-item');
                studentItems.forEach(item => {
                    const studentName = item.querySelector('label').textContent.toLowerCase();
                    if (studentName.includes(searchValue)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            </script>
            </body>
            </html>