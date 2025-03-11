<?php
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

require_once 'adminSession.php';
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            try {
                $pdo->beginTransaction();
                
                // Check if this is a folder or regular class
                $isFolder = isset($_POST['is_folder']) && $_POST['is_folder'] == '1';
                $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
                
                $stmt = $pdo->prepare("INSERT INTO classes (name, description, created_by, parent_id, is_folder) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'], 
                    $_POST['description'], 
                    $_SESSION['user_id'],
                    $parentId,
                    $isFolder
                ]);
                $classId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_id, is_owner, can_modify) VALUES (?, ?, true, true)");
                $stmt->execute([$_SESSION['user_id'], $classId]);
                
                $pdo->commit();
                $_SESSION['success'] = $isFolder ? "Folder created successfully" : "Class created successfully";
                header('Location: classes.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Error creating " . ($isFolder ? "folder" : "class");
                header('Location: classes.php');
                exit;
            }
            break;

        case 'update':
            try {
                $pdo->beginTransaction();
                
                // Get the class details to check if it's a folder
                $stmt = $pdo->prepare("SELECT is_folder FROM classes WHERE id = ?");
                $stmt->execute([$_POST['class_id']]);
                $isFolder = $stmt->fetchColumn();
                
                // Update the class/folder details
                $stmt = $pdo->prepare("UPDATE classes SET name = ?, description = ?, parent_id = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'], 
                    $_POST['description'], 
                    !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                    $_POST['class_id']
                ]);
                
                $pdo->commit();
                $_SESSION['success'] = $isFolder ? "Folder updated successfully" : "Class updated successfully";
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Error updating " . ($isFolder ? "folder" : "class");
            }
            header('Location: classes.php');
            exit;
            break;

        case 'delete':
            try {
                $pdo->beginTransaction();
                
                // Get the class details to check if it's a folder
                $stmt = $pdo->prepare("SELECT is_folder FROM classes WHERE id = ?");
                $stmt->execute([$_POST['class_id']]);
                $isFolder = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
                $stmt->execute([$_POST['class_id']]);
                $pdo->commit();
                $_SESSION['success'] = $isFolder ? "Folder deleted successfully." : "Class deleted successfully.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Error deleting " . ($isFolder ? "folder" : "class") . ".";
            }
            header('Location: classes.php');
            exit;
            break;

        case 'enroll':
            try {
                $pdo->beginTransaction();
                
                // Check if we're enrolling in a folder or class
                $stmt = $pdo->prepare("SELECT is_folder FROM classes WHERE id = ?");
                $stmt->execute([$_POST['class_id']]);
                $isFolder = $stmt->fetchColumn();
                
                // Prepare the statement for enrollment
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO enrollments (student_id, class_id, teacher_id, is_folder_access) 
                    SELECT ?, ?, ?, ? 
                    FROM teacher_students 
                    WHERE teacher_id = ? AND student_id = ? AND status = 'accepted'
                ");
                
                foreach ($_POST['students'] as $studentId) {
                    $teacherId = $_SESSION['role'] === 'teacher' ? $_SESSION['user_id'] : $_POST['teacherId'];
                    $stmt->execute([
                        $studentId, 
                        $_POST['class_id'],
                        $teacherId,
                        $isFolder,
                        $teacherId,
                        $studentId
                    ]);
                    
                    // If this is a folder, optionally enroll in all child classes as well
                    if ($isFolder && isset($_POST['enroll_all_children']) && $_POST['enroll_all_children'] == '1') {
                        $childClasses = getChildClasses($_POST['class_id'], $pdo);
                        foreach ($childClasses as $childClass) {
                            $stmt->execute([
                                $studentId, 
                                $childClass['id'],
                                $teacherId,
                                false, // Not folder access for individual classes
                                $teacherId,
                                $studentId
                            ]);
                        }
                    }
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Students enrolled successfully";
                header('Location: classes.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $_SESSION['error'] = "Error enrolling students";
                header('Location: classes.php');
                exit;
            }
            break;

        case 'unenroll':
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND class_id = ? AND teacher_id = ?");
            $stmt->execute([$_POST['student_id'], $_POST['class_id'], $_SESSION['user_id']]);
            echo json_encode(['success' => true]);
            exit;
            break;
    }
}

// Function to get all child classes of a folder
function getChildClasses($folderId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT id, name, is_folder
        FROM classes
        WHERE parent_id = ?
    ");
    $stmt->execute([$folderId]);
    $children = $stmt->fetchAll();
    
    $allChildren = [];
    foreach ($children as $child) {
        $allChildren[] = $child;
        if ($child['is_folder']) {
            // Recursively get children of folders
            $subChildren = getChildClasses($child['id'], $pdo);
            $allChildren = array_merge($allChildren, $subChildren);
        }
    }
    
    return $allChildren;
}

// Handle AJAX requests for viewing enrolled students
if (isset($_GET['action']) && $_GET['action'] === 'get_enrolled_students') {
    $classId = $_GET['class_id'];
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.last_access,
                   e.is_folder_access
            FROM users u 
            JOIN enrollments e ON u.id = e.student_id 
            WHERE e.class_id = ? AND e.teacher_id = ?
        ");
        $stmt->execute([$classId, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.last_access,
                   t.name as teacher_name,
                   e.is_folder_access
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

// Get parent folder list for dropdown
$stmt = $pdo->prepare("
    SELECT id, name, parent_id 
    FROM classes 
    WHERE is_folder = 1
    ORDER BY name
");
$stmt->execute();
$folders = $stmt->fetchAll();

// Build a hierarchical folder structure
function buildFolderHierarchy($folders, $parentId = null, $level = 0) {
    $result = [];
    foreach ($folders as $folder) {
        if ($folder['parent_id'] == $parentId) {
            $folder['level'] = $level;
            $result[] = $folder;
            $children = buildFolderHierarchy($folders, $folder['id'], $level + 1);
            $result = array_merge($result, $children);
        }
    }
    return $result;
}
$hierarchicalFolders = buildFolderHierarchy($folders);

// Fetch classes based on role
if ($_SESSION['role'] === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT e.student_id) as student_count,
               p.name as parent_name
        FROM classes c 
        LEFT JOIN enrollments e ON c.id = e.class_id AND e.teacher_id = ?
        JOIN teacher_classes tc ON c.id = tc.class_id 
        LEFT JOIN classes p ON c.parent_id = p.id
        WHERE tc.teacher_id = ?
        GROUP BY c.id 
        ORDER BY c.is_folder DESC, c.name
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
        SELECT c.*, 
               COUNT(DISTINCT e.student_id) as student_count,
               u.name as creator_name, u.email as creator_email,
               p.name as parent_name
        FROM classes c 
        LEFT JOIN enrollments e ON c.id = e.class_id 
        LEFT JOIN users u ON c.created_by = u.id
        LEFT JOIN classes p ON c.parent_id = p.id
        GROUP BY c.id 
        ORDER BY c.is_folder DESC, c.name
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
    <title>Classes & Folders Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include_once 'admin-header.php'; ?>
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
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Classes & Folders Management</h2>
            <div class="space-x-2">
                <button onclick="openAddModal('class')" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Add New Class
                </button>
                <button onclick="openAddModal('folder')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Add New Folder
                </button>
            </div>
        </div>

        <!-- Classes List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
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
                    <tr class="<?= $class['is_folder'] ? 'bg-blue-50' : '' ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php if ($class['is_folder']): ?>
                                <i class="fas fa-folder text-yellow-500 mr-2"></i>
                            <?php else: ?>
                                <i class="fas fa-book text-indigo-500 mr-2"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($class['name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $class['is_folder'] ? 'Folder' : 'Class' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $class['parent_name'] ? htmlspecialchars($class['parent_name']) : '-' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php if ($_SESSION['role'] !== 'teacher'): ?>
                                <?= htmlspecialchars($class['creator_name']) ?>  
                                (<?= htmlspecialchars($class['creator_email']) ?>)
                            <?php endif; ?>
                        </td>
                        <input type="hidden" name="ClassteacherId" id="ClassteacherId" value="<?= htmlspecialchars($class['created_by']) ?>">
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($class['description']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <button onclick="viewStudents(<?= $class['id'] ?>)" class="text-indigo-600 hover:text-indigo-900">
                                <?= $class['student_count'] ?> students
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEnrollModal(<?= $class['id'] ?>, <?= $class['is_folder'] ? 'true' : 'false' ?>)" 
                                    class="text-green-600 hover:text-green-900">Enroll</button>
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($class)) ?>)" 
                                    class="ml-4 text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button onclick="confirmDelete(<?= $class['id'] ?>, '<?= $class['is_folder'] ? 'folder' : 'class' ?>')" 
                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Class/Folder Modal -->
    <div id="classModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="class_id" id="classId">
                <input type="hidden" name="is_folder" id="isFolder" value="0">
                
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Class</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="className" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Folder (Optional)</label>
                        <select name="parent_id" id="parentFolder" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- None (Root Level) --</option>
                            <?php foreach ($hierarchicalFolders as $folder): ?>
                                <option value="<?= $folder['id'] ?>">
                                    <?= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $folder['level']) ?>
                                    <?= htmlspecialchars($folder['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
            <form method="POST" >
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="class_id" id="enrollClassId">
                <input type="hidden" name="teacherID" id="teacherID">
                
                <div id="folderEnrollOptions" class="mb-4 hidden">
                    <div class="flex items-center">
                        <input type="checkbox" name="enroll_all_children" value="1" id="enrollAllChildren" 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="enrollAllChildren" class="ml-2 block text-sm text-gray-900">
                            Enroll in all classes within this folder
                        </label>
                    </div>
                </div>
                
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
        function openAddModal(type) {
            document.getElementById('modalTitle').textContent = type === 'folder' ? 'Add New Folder' : 'Add New Class';
            document.getElementById('formAction').value = 'create';
            document.getElementById('classId').value = '';
            document.getElementById('className').value = '';
            document.getElementById('classDescription').value = '';
            document.getElementById('parentFolder').selectedIndex = 0;
            document.getElementById('isFolder').value = type === 'folder' ? '1' : '0';
            document.getElementById('classModal').classList.remove('hidden');
        }

        function openEditModal(classData) {
            document.getElementById('modalTitle').textContent = classData.is_folder == 1 ? 'Edit Folder' : 'Edit Class';
            document.getElementById('formAction').value = 'update';
            document.getElementById('classId').value = classData.id;
            document.getElementById('className').value = classData.name;
            document.getElementById('classDescription').value = classData.description;
            document.getElementById('isFolder').value = classData.is_folder;
            
            // Set parent folder selection
            const parentSelect = document.getElementById('parentFolder');
            if (classData.parent_id) {
                for (let i = 0; i < parentSelect.options.length; i++) {
                    if (parentSelect.options[i].value == classData.parent_id) {
                        parentSelect.selectedIndex = i;
                        break;
                    }
                }
            } else {
                parentSelect.selectedIndex = 0;
            }
            
            document.getElementById('classModal').classList.remove('hidden');
        }

        function openEnrollModal(classId, isFolder) {
            document.getElementById('enrollClassId').value = classId;
            document.getElementById('teacherID').value = document.getElementById('ClassteacherId').value;
            
            // Show or hide folder-specific options
            const folderOptions = document.getElementById('folderEnrollOptions');
            if (isFolder) {
                folderOptions.classList.remove('hidden');
            } else {
                folderOptions.classList.add('hidden');
            }
            
            document.getElementById('enrollModal').classList.remove('hidden');
        }

        async function viewStudents(classId) {
            try {
                const response = await fetch(`?action=get_enrolled_students&class_id=${classId}`);
                const data = await response.json();
                
                const studentsList = document.getElementById('enrolledStudentsList');
                studentsList.innerHTML = data.students.map(student => `
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div>
                            <span>${student.name} (${student.email})</span>
                            ${student.is_folder_access ? '<span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Folder Access</span>' : ''}
                        </div>
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

        function confirmDelete(id, type) {
            const message = `Are you sure you want to delete this ${type}?`;
            const warning = type === 'folder' ? '\n\nWARNING: This will also delete all classes and subfolders inside it!' : '';
            
            if (confirm(message + warning)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="class_id" value="${id}">
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