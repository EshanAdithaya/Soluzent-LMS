<?php
/**
 * Controller for folder and class management
 * Contains all form processing logic and AJAX handlers
 */
// No session_start() here since it's called in the including file
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
// adminSession.php is included in the parent file

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_folder':
            createFolder($pdo);
            break;

        case 'create_class':
            createClass($pdo);
            break;

        case 'update_folder':
            updateFolder($pdo);
            break;

        case 'update_class':
            updateClass($pdo);
            break;

        case 'delete_folder':
            deleteFolder($pdo);
            break;

        case 'delete_class':
            deleteClass($pdo);
            break;

        case 'enroll':
            enrollStudents($pdo);
            break;

        case 'unenroll':
            unenrollStudent($pdo);
            break;
            
        case 'unenroll_from_folder':
            unenrollStudentFromFolder($pdo);
            break;
    }
}

// Handle AJAX requests for viewing enrolled students
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_enrolled_students' && isset($_GET['class_id'])) {
        getEnrolledStudents($pdo);
    } elseif ($_GET['action'] === 'get_folder_students' && isset($_GET['folder_id'])) {
        getFolderEnrolledStudents($pdo);
    }
}

/**
 * Create a new folder
 */
function createFolder($pdo) {
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
}

/**
 * Create a new class
 */
function createClass($pdo) {
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
        
        // Create the class
        $stmt = $pdo->prepare("
            INSERT INTO classes (name, description, created_by, is_folder, folder_id) 
            VALUES (?, ?, ?, 0, ?)
        ");
        
        if (!$stmt->execute([
            $_POST['name'], 
            $_POST['description'], 
            $_SESSION['user_id'],
            $folderId
        ])) {
            throw new Exception("Database error creating class");
        }
        
        $classId = $pdo->lastInsertId();
        
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
}

/**
 * Update an existing folder
 */
function updateFolder($pdo) {
    try {
        $pdo->beginTransaction();
        
        $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $folderId = intval($_POST['folder_id']);
        
        // Validate parent folder exists if specified
        if ($parentId) {
            $checkParent = $pdo->prepare("SELECT id FROM folders WHERE id = ?");
            $checkParent->execute([$parentId]);
            $parent = $checkParent->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent) {
                throw new Exception("Parent folder not found");
            }
            
            // Prevent circular references
            if ($parentId == $folderId) {
                throw new Exception("A folder cannot be its own parent");
            }
            
            // Check for deeper circular references
            if (isDescendant($pdo, $folderId, $parentId)) {
                throw new Exception("Cannot create circular folder references");
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
            $folderId
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
}

/**
 * Check if targetId is a descendant of parentId in the folder hierarchy
 */
function isDescendant($pdo, $targetId, $parentId) {
    $currentId = $parentId;
    $visited = array();
    
    while ($currentId !== null) {
        // Check for loops in the hierarchy
        if (in_array($currentId, $visited)) {
            return false;
        }
        $visited[] = $currentId;
        
        if ($currentId == $targetId) {
            return true;
        }
        
        $stmt = $pdo->prepare("SELECT parent_id FROM folders WHERE id = ?");
        $stmt->execute([$currentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
        $currentId = $result['parent_id'];
    }
    
    return false;
}

/**
 * Update an existing class
 */
function updateClass($pdo) {
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
}

/**
 * Delete a folder
 */
function deleteFolder($pdo) {
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
}

/**
 * Delete a class
 */
function deleteClass($pdo) {
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
}

/**
 * Enroll students in a class or folder
 */
function enrollStudents($pdo) {
    try {
        $pdo->beginTransaction();
        
        $classId = $_POST['class_id'];
        $isFolderAccess = isset($_POST['is_folder_access']) ? 1 : 0;
        
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
                $classId,
                $teacherId,
                $isFolderAccess,
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
}

/**
 * Unenroll a student from a class
 */
function unenrollStudent($pdo) {
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND class_id = ? AND teacher_id = ?");
    $stmt->execute([$_POST['student_id'], $_POST['class_id'], $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Get enrolled students for a class
 */
function getEnrolledStudents($pdo) {
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

/**
 * Get enrolled students for a folder
 */
function getFolderEnrolledStudents($pdo) {
    $folderId = $_GET['folder_id'];
    
    if ($_SESSION['role'] === 'teacher') {
        // For teachers, only show students they've given folder access to
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.last_access,
                   e.is_folder_access
            FROM users u 
            JOIN enrollments e ON u.id = e.student_id 
            JOIN classes c ON e.class_id = c.id
            WHERE c.folder_id = ? AND e.teacher_id = ? AND e.is_folder_access = 1
            GROUP BY u.id
        ");
        $stmt->execute([$folderId, $_SESSION['user_id']]);
    } else {
        // For admins, show all students with folder access
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.last_access,
                   t.name as teacher_name,
                   e.is_folder_access
            FROM users u 
            JOIN enrollments e ON u.id = e.student_id 
            JOIN users t ON e.teacher_id = t.id
            JOIN classes c ON e.class_id = c.id
            WHERE c.folder_id = ? AND e.is_folder_access = 1
            GROUP BY u.id
        ");
        $stmt->execute([$folderId]);
    }
    $students = $stmt->fetchAll();
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}

/**
 * Unenroll a student from all classes in a folder with folder-level access
 */
function unenrollStudentFromFolder($pdo) {
    try {
        $pdo->beginTransaction();
        
        $folderId = $_POST['folder_id'];
        $studentId = $_POST['student_id'];
        
        // Get all classes in this folder
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE folder_id = ?");
        $stmt->execute([$folderId]);
        $classIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($classIds)) {
            // Remove folder access for all classes in this folder
            $placeholders = implode(',', array_fill(0, count($classIds), '?'));
            $params = array_merge([$studentId], $classIds);
            
            $stmt = $pdo->prepare("
                DELETE FROM enrollments 
                WHERE student_id = ? 
                AND class_id IN ($placeholders) 
                AND is_folder_access = 1
            ");
            $stmt->execute($params);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error removing folder access: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}