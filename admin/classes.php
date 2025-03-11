<?php
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

require_once 'adminSession.php';
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_folder':
            try {
                $pdo->beginTransaction();
                
                $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
                
                // Validate parent folder exists if specified
                if ($parentId) {
                    $checkParent = $pdo->prepare("SELECT id FROM folders WHERE id = ?");
                    $checkParent->execute([$parentId]);
                    $parent = $checkParent->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$parent) {
                        throw new Exception("Parent folder not found");
                    }
                }
                
                // Create the folder
                $stmt = $pdo->prepare("
                    INSERT INTO folders (name, description, parent_id, created_by) 
                    VALUES (?, ?, ?, ?)
                ");
                
                if (!$stmt->execute([
                    $_POST['name'], 
                    $_POST['description'], 
                    $parentId,
                    $_SESSION['user_id']
                ])) {
                    throw new Exception("Database error creating folder");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Folder created successfully";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error creating folder: " . $e->getMessage());
                $_SESSION['error'] = "Error creating folder: " . $e->getMessage();
                header('Location: classes.php');
                exit;
            }
            break;

        case 'create_class':
            try {
                $pdo->beginTransaction();
                
                // Instead of using parent_id directly, store the folder ID in a different way
                // This can be in a separate column or in a related table
                $folderId = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
                
                // Validate folder exists if specified
                if ($folderId) {
                    $checkFolder = $pdo->prepare("SELECT id FROM folders WHERE id = ?");
                    $checkFolder->execute([$folderId]);
                    $folder = $checkFolder->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$folder) {
                        throw new Exception("Folder not found");
                    }
                    
                    // Create the class with null parent_id (don't use the folder_id as parent_id)
                    $stmt = $pdo->prepare("
                        INSERT INTO classes (name, description, created_by, is_folder) 
                        VALUES (?, ?, ?, 0)
                    ");
                    
                    if (!$stmt->execute([
                        $_POST['name'], 
                        $_POST['description'], 
                        $_SESSION['user_id']
                    ])) {
                        throw new Exception("Database error creating class");
                    }
                    
                    $classId = $pdo->lastInsertId();
                    
                    // Create a class-folder relationship in a new table or another way
                    // For now, since we don't have a dedicated table, let's use a naming convention or metadata
                    // You might want to create a class_folders table later
                    
                    // Track the folder relationship separately
                    $stmt = $pdo->prepare("
                        UPDATE classes 
                        SET folder_id = ? 
                        WHERE id = ?
                    ");
                    
                    // Modify this based on your actual database structure
                    // This assumes you've added a folder_id column to the classes table
                    if (!$stmt->execute([$folderId, $classId])) {
                        throw new Exception("Database error associating class with folder");
                    }
                    
                } else {
                    // Create the class without a folder association
                    $stmt = $pdo->prepare("
                        INSERT INTO classes (name, description, created_by, is_folder) 
                        VALUES (?, ?, ?, 0)
                    ");
                    
                    if (!$stmt->execute([
                        $_POST['name'], 
                        $_POST['description'], 
                        $_SESSION['user_id']
                    ])) {
                        throw new Exception("Database error creating class");
                    }
                    
                    $classId = $pdo->lastInsertId();
                }
                
                // Associate the class with the teacher
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_classes (teacher_id, class_id, is_owner, can_modify) 
                    VALUES (?, ?, true, true)
                ");
                
                if (!$stmt->execute([$_SESSION['user_id'], $classId])) {
                    throw new Exception("Database error associating teacher with class");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Class created successfully";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error creating class: " . $e->getMessage());
                $_SESSION['error'] = "Error creating class: " . $e->getMessage();
                header('Location: classes.php');
                exit;
            }
            break;

        case 'update_folder':
            try {
                $pdo->beginTransaction();
                
                $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
                
                // Validate parent folder exists if specified
                if ($parentId) {
                    $checkParent = $pdo->prepare("SELECT id FROM folders WHERE id = ?");
                    $checkParent->execute([$parentId]);
                    $parent = $checkParent->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$parent) {
                        throw new Exception("Parent folder not found");
                    }
                    
                    // Prevent circular references
                    if ($parentId == $_POST['folder_id']) {
                        throw new Exception("A folder cannot be its own parent");
                    }
                    
                    // Check for deeper circular references
                    $currentParentId = $parentId;
                    while ($currentParentId !== null) {
                        $checkAncestor = $pdo->prepare("SELECT parent_id FROM folders WHERE id = ?");
                        $checkAncestor->execute([$currentParentId]);
                        $ancestor = $checkAncestor->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$ancestor) {
                            break;
                        }
                        
                        if ($ancestor['parent_id'] == $_POST['folder_id']) {
                            throw new Exception("Cannot create circular folder references");
                        }
                        
                        $currentParentId = $ancestor['parent_id'];
                    }
                }
                
                // Update the folder
                $stmt = $pdo->prepare("
                    UPDATE folders 
                    SET name = ?, description = ?, parent_id = ?
                    WHERE id = ?
                ");
                
                if (!$stmt->execute([
                    $_POST['name'], 
                    $_POST['description'], 
                    $parentId,
                    $_POST['folder_id']
                ])) {
                    throw new Exception("Database error updating folder");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Folder updated successfully";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error updating folder: " . $e->getMessage());
                $_SESSION['error'] = "Error updating folder: " . $e->getMessage();
                header('Location: classes.php');
                exit;
            }
            break;

        case 'update_class':
            try {
                $pdo->beginTransaction();
                
                $folderId = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
                
                // Validate folder exists if specified
                if ($folderId) {
                    $checkFolder = $pdo->prepare("SELECT id FROM folders WHERE id = ?");
                    $checkFolder->execute([$folderId]);
                    $folder = $checkFolder->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$folder) {
                        throw new Exception("Folder not found");
                    }
                }
                
                // Update the class
                $stmt = $pdo->prepare("
                    UPDATE classes 
                    SET name = ?, description = ?, folder_id = ?
                    WHERE id = ?
                ");
                
                if (!$stmt->execute([
                    $_POST['name'], 
                    $_POST['description'], 
                    $folderId,
                    $_POST['class_id']
                ])) {
                    throw new Exception("Database error updating class");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Class updated successfully";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error updating class: " . $e->getMessage());
                $_SESSION['error'] = "Error updating class: " . $e->getMessage();
                header('Location: classes.php');
                exit;
            }
            break;

        case 'delete_folder':
            try {
                $pdo->beginTransaction();
                
                // Update any classes that reference this folder to have null folder_id
                $stmt = $pdo->prepare("
                    UPDATE classes 
                    SET folder_id = NULL 
                    WHERE folder_id = ?
                ");
                $stmt->execute([$_POST['folder_id']]);
                
                // Delete the folder
                $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ?");
                if (!$stmt->execute([$_POST['folder_id']])) {
                    throw new Exception("Database error deleting folder");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Folder deleted successfully.";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error deleting folder: " . $e->getMessage());
                $_SESSION['error'] = "Error deleting folder: " . $e->getMessage();
                header('Location: classes.php');
                exit;
            }
            break;

        case 'delete_class':
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ? AND is_folder = 0");
                if (!$stmt->execute([$_POST['class_id']])) {
                    throw new Exception("Database error deleting class");
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Class deleted successfully.";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error deleting class: " . $e->getMessage());
                $_SESSION['error'] = "Error deleting class: " . $e->getMessage();
                header('Location: classes.php');
                exit;
            }
            break;

        case 'enroll':
            try {
                $pdo->beginTransaction();
                
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
                        isset($_POST['is_folder_access']) ? 1 : 0,
                        $teacherId,
                        $studentId
                    ]);
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Students enrolled successfully";
                header('Location: classes.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error enrolling students: " . $e->getMessage());
                $_SESSION['error'] = "Error enrolling students: " . $e->getMessage();
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

// First, check if the classes table has the folder_id column and add it if not
try {
    // Check if folder_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM classes LIKE 'folder_id'");
    $folderIdColumnExists = $stmt->rowCount() > 0;
    
    if (!$folderIdColumnExists) {
        // Add folder_id column
        $pdo->exec("ALTER TABLE classes ADD COLUMN folder_id INT NULL");
        $pdo->exec("ALTER TABLE classes ADD CONSTRAINT fk_class_folder FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL");
    }
} catch (PDOException $e) {
    // Log the error but continue
    error_log("Error checking or adding folder_id column: " . $e->getMessage());
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

// Fetch all folders
$stmt = $pdo->query("
    SELECT f.*, u.name as creator_name, u.email as creator_email, 
           p.name as parent_name
    FROM folders f
    LEFT JOIN users u ON f.created_by = u.id
    LEFT JOIN folders p ON f.parent_id = p.id
    ORDER BY f.name
");
$foldersRaw = $stmt->fetchAll();
$hierarchicalFolders = buildFolderHierarchy($foldersRaw);

// Get all classes with their folder information
if ($_SESSION['role'] === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT e.student_id) as student_count,
               f.name as folder_name, f.id as folder_id,
               u.name as creator_name, u.email as creator_email
        FROM classes c 
        LEFT JOIN enrollments e ON c.id = e.class_id AND e.teacher_id = ?
        JOIN teacher_classes tc ON c.id = tc.class_id 
        LEFT JOIN folders f ON c.folder_id = f.id
        LEFT JOIN users u ON c.created_by = u.id
        WHERE tc.teacher_id = ? AND c.is_folder = 0
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
        SELECT c.*, 
               COUNT(DISTINCT e.student_id) as student_count,
               f.name as folder_name, f.id as folder_id,
               u.name as creator_name, u.email as creator_email
        FROM classes c 
        LEFT JOIN enrollments e ON c.id = e.class_id 
        LEFT JOIN folders f ON c.folder_id = f.id
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.is_folder = 0
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $classes = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name");
}
$students = $stmt->fetchAll();

// Group classes by folder
$classesByFolder = [];
foreach ($classes as $class) {
    $folderId = $class['folder_id'] ?? 'none';
    if (!isset($classesByFolder[$folderId])) {
        $classesByFolder[$folderId] = [];
    }
    $classesByFolder[$folderId][] = $class;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes & Folders Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .folder-icon {
            color: #f6c23e;
        }
        .class-icon {
            color: #5a5cd1;
        }
        .nested-item {
            transition: background-color 0.2s;
        }
        .nested-item:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
    </style>
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

        <!-- Unified Hierarchical View -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Folder Structure and Classes
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Manage your folders and classes in a hierarchical structure
                </p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php if ($_SESSION['role'] !== 'teacher'): ?>Created By<?php endif; ?>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Students
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // Recursive function to display folders and their contents
                        function displayFolderAndClassesHierarchy($folders, $classesByFolder, $parentId = null, $level = 0) {
                            // First display all folders at current level
                            foreach ($folders as $folder) {
                                if ($folder['parent_id'] == $parentId) {
                                    ?>
                                    <tr class="bg-blue-50 nested-item">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div style="padding-left: <?= $level * 20 ?>px;">
                                                <i class="fas fa-folder folder-icon mr-2"></i>
                                                <?= htmlspecialchars($folder['name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            Folder
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($_SESSION['role'] !== 'teacher'): ?>
                                                <?= htmlspecialchars($folder['creator_name']) ?>
                                                (<?= htmlspecialchars($folder['creator_email']) ?>)
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?= htmlspecialchars($folder['description']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            -
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="openEditFolderModal(<?= htmlspecialchars(json_encode($folder)) ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            <button onclick="confirmDeleteFolder(<?= $folder['id'] ?>)" 
                                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                    <?php
                                    
                                    // Display classes in this folder if any
                                    if (isset($classesByFolder[$folder['id']])) {
                                        foreach ($classesByFolder[$folder['id']] as $class) {
                                            ?>
                                            <tr class="nested-item">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <div style="padding-left: <?= ($level + 1) * 20 ?>px;">
                                                        <i class="fas fa-book class-icon mr-2"></i>
                                                        <?= htmlspecialchars($class['name']) ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    Class
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php if ($_SESSION['role'] !== 'teacher'): ?>
                                                        <?= htmlspecialchars($class['creator_name']) ?>
                                                        (<?= htmlspecialchars($class['creator_email']) ?>)
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?= htmlspecialchars($class['description']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <button onclick="viewStudents(<?= $class['id'] ?>)" class="text-indigo-600 hover:text-indigo-900">
                                                        <?= $class['student_count'] ?> students
                                                    </button>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button onclick="openEnrollModal(<?= $class['id'] ?>)" 
                                                            class="text-green-600 hover:text-green-900">Enroll</button>
                                                    <button onclick="openEditClassModal(<?= htmlspecialchars(json_encode($class)) ?>)" 
                                                            class="ml-2 text-indigo-600 hover:text-indigo-900">Edit</button>
                                                    <button onclick="confirmDeleteClass(<?= $class['id'] ?>)" 
                                                            class="ml-2 text-red-600 hover:text-red-900">Delete</button>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    
                                    // Recursively display child folders
                                    displayFolderAndClassesHierarchy($folders, $classesByFolder, $folder['id'], $level + 1);
                                }
                            }
                        }

                        // Display the hierarchy starting from root folders
                        displayFolderAndClassesHierarchy($foldersRaw, $classesByFolder);
                        
                        // Display classes not in any folder
                        if (isset($classesByFolder['none']) && !empty($classesByFolder['none'])):
                        ?>
                        <tr class="bg-gray-100">
                            <td colspan="6" class="px-6 py-3 text-sm font-medium text-gray-500">
                                Classes (Not in folders)
                            </td>
                        </tr>
                        <?php foreach ($classesByFolder['none'] as $class): ?>
                        <tr class="nested-item">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <div style="padding-left: 20px;">
                                    <i class="fas fa-book class-icon mr-2"></i>
                                    <?= htmlspecialchars($class['name']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                Class
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($_SESSION['role'] !== 'teacher'): ?>
                                    <?= htmlspecialchars($class['creator_name']) ?>
                                    (<?= htmlspecialchars($class['creator_email']) ?>)
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($class['description']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button onclick="viewStudents(<?= $class['id'] ?>)" class="text-indigo-600 hover:text-indigo-900">
                                    <?= $class['student_count'] ?> students
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openEnrollModal(<?= $class['id'] ?>)" 
                                        class="text-green-600 hover:text-green-900">Enroll</button>
                                <button onclick="openEditClassModal(<?= htmlspecialchars(json_encode($class)) ?>)" 
                                        class="ml-2 text-indigo-600 hover:text-indigo-900">Edit</button>
                                <button onclick="confirmDeleteClass(<?= $class['id'] ?>)" 
                                        class="ml-2 text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Folder Modal -->
    <div id="folderModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form method="POST">
                <input type="hidden" name="action" id="folderFormAction" value="create_folder">
                <input type="hidden" name="folder_id" id="folderId">
                
                <h3 id="folderModalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Folder</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Folder Name</label>
                        <input type="text" name="name" id="folderName" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Folder (Optional)</label>
                        <select name="parent_id" id="parentFolderId" 
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
                        <textarea name="description" id="folderDescription" rows="3" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('folderModal')"
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

    <!-- Add Class Modal -->
    <div id="classModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form method="POST">
                <input type="hidden" name="action" id="classFormAction" value="create_class">
                <input type="hidden" name="class_id" id="classId">
                
                <h3 id="classModalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Class</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Class Name</label>
                        <input type="text" name="name" id="className" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="folder_id" class="block text-sm font-medium text-gray-700">Folder (Optional)</label>
                        <select name="folder_id" id="folderIdSelect" 
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
            <form method="POST">
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="class_id" id="enrollClassId">
                <input type="hidden" name="teacherID" id="teacherID">
                
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
            if (type === 'folder') {
                document.getElementById('folderModalTitle').textContent = 'Add New Folder';
                document.getElementById('folderFormAction').value = 'create_folder';
                document.getElementById('folderId').value = '';
                document.getElementById('folderName').value = '';
                document.getElementById('folderDescription').value = '';
                document.getElementById('parentFolderId').selectedIndex = 0;
                document.getElementById('folderModal').classList.remove('hidden');
            } else {
                document.getElementById('classModalTitle').textContent = 'Add New Class';
                document.getElementById('classFormAction').value = 'create_class';
                document.getElementById('classId').value = '';
                document.getElementById('className').value = '';
                document.getElementById('classDescription').value = '';
                document.getElementById('folderIdSelect').selectedIndex = 0;
                document.getElementById('classModal').classList.remove('hidden');
            }
        }

        function openEditFolderModal(folder) {
            document.getElementById('folderModalTitle').textContent = 'Edit Folder';
            document.getElementById('folderFormAction').value = 'update_folder';
            document.getElementById('folderId').value = folder.id;
            document.getElementById('folderName').value = folder.name;
            document.getElementById('folderDescription').value = folder.description;
            
            // Set parent folder selection
            const parentSelect = document.getElementById('parentFolderId');
            if (folder.parent_id) {
                for (let i = 0; i < parentSelect.options.length; i++) {
                    if (parentSelect.options[i].value == folder.parent_id) {
                        parentSelect.selectedIndex = i;
                        break;
                    }
                }
            } else {
                parentSelect.selectedIndex = 0;
            }
            
            document.getElementById('folderModal').classList.remove('hidden');
        }

        function openEditClassModal(classObj) {
            document.getElementById('classModalTitle').textContent = 'Edit Class';
            document.getElementById('classFormAction').value = 'update_class';
            document.getElementById('classId').value = classObj.id;
            document.getElementById('className').value = classObj.name;
            document.getElementById('classDescription').value = classObj.description;
            
            // Set folder selection
            const folderSelect = document.getElementById('folderIdSelect');
            if (classObj.folder_id) {
                for (let i = 0; i < folderSelect.options.length; i++) {
                    if (folderSelect.options[i].value == classObj.folder_id) {
                        folderSelect.selectedIndex = i;
                        break;
                    }
                }
            } else {
                folderSelect.selectedIndex = 0;
            }
            
            document.getElementById('classModal').classList.remove('hidden');
        }

        function openEnrollModal(classId) {
            document.getElementById('enrollClassId').value = classId;
            document.getElementById('teacherID').value = document.getElementById('ClassteacherId').value;
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

        function confirmDeleteFolder(id) {
            const message = "Are you sure you want to delete this folder?\n\nWARNING: Classes in this folder will be moved to root level.";
            
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_folder">
                    <input type="hidden" name="folder_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmDeleteClass(id) {
            if (confirm('Are you sure you want to delete this class?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_class">
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
            const modals = ['folderModal', 'classModal', 'enrollModal', 'viewStudentsModal'];
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
                const modals = ['folderModal', 'classModal', 'enrollModal', 'viewStudentsModal'];
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