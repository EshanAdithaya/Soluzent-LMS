<?php
/**
 * Model for folder and class data
 * Handles data retrieval and hierarchy building
 */
// No additional requires here - they're all in the parent file

/**
 * Ensure the classes table has the folder_id column
 */
function ensureFolderIdColumn($pdo) {
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
}

/**
 * Build a hierarchical folder structure
 */
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

/**
 * Get all folders with hierarchy information
 */
function getAllFolders($pdo) {
    $stmt = $pdo->query("
        SELECT f.*, u.name as creator_name, u.email as creator_email, 
               p.name as parent_name
        FROM folders f
        LEFT JOIN users u ON f.created_by = u.id
        LEFT JOIN folders p ON f.parent_id = p.id
        ORDER BY f.name
    ");
    $foldersRaw = $stmt->fetchAll();
    return buildFolderHierarchy($foldersRaw);
}

/**
 * Get all classes with their folder information
 */
function getAllClasses($pdo) {
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
    }
    return $stmt->fetchAll();
}

/**
 * Get all students that the teacher can enroll
 */
function getEnrollableStudents($pdo) {
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email 
            FROM users u 
            JOIN teacher_students ts ON u.id = ts.student_id
            WHERE ts.teacher_id = ? AND ts.status = 'accepted'
            ORDER BY u.name
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name");
    }
    return $stmt->fetchAll();
}

/**
 * Group classes by folder
 */
function groupClassesByFolder($classes) {
    $classesByFolder = [];
    foreach ($classes as $class) {
        $folderId = $class['folder_id'] ?? 'none';
        if (!isset($classesByFolder[$folderId])) {
            $classesByFolder[$folderId] = [];
        }
        $classesByFolder[$folderId][] = $class;
    }
    return $classesByFolder;
}

/**
 * Get all folder IDs that are descendants of the given folder
 */
function getAllDescendantFolderIds($pdo, $folderId) {
    $descendants = [];
    $queue = [$folderId];
    
    while (!empty($queue)) {
        $currentId = array_shift($queue);
        
        $stmt = $pdo->prepare("SELECT id FROM folders WHERE parent_id = ?");
        $stmt->execute([$currentId]);
        $childFolders = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($childFolders as $childId) {
            $descendants[] = $childId;
            $queue[] = $childId;
        }
    }
    
    return $descendants;
}

/**
 * Get all class IDs within a folder and its descendants
 */
function getAllClassesInFolderHierarchy($pdo, $folderId) {
    $classIds = [];
    
    // First, get direct classes in this folder
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE folder_id = ? AND is_folder = 0");
    $stmt->execute([$folderId]);
    $directClasses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $classIds = array_merge($classIds, $directClasses);
    
    // Then, get classes in descendant folders
    $descendantFolders = getAllDescendantFolderIds($pdo, $folderId);
    
    if (!empty($descendantFolders)) {
        $placeholders = implode(',', array_fill(0, count($descendantFolders), '?'));
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE folder_id IN ($placeholders) AND is_folder = 0");
        $stmt->execute($descendantFolders);
        $descendantClasses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $classIds = array_merge($classIds, $descendantClasses);
    }
    
    return $classIds;
}

/**
 * Check if a student has access to a specific class
 * Either through direct enrollment or folder-level access
 */
function hasAccessToClass($pdo, $studentId, $classId) {
    // Check direct class access
    $stmt = $pdo->prepare("
        SELECT 1 FROM enrollments 
        WHERE student_id = ? AND class_id = ? AND is_folder_access = 0
    ");
    $stmt->execute([$studentId, $classId]);
    if ($stmt->rowCount() > 0) {
        return true;
    }
    
    // Check folder access
    $stmt = $pdo->prepare("SELECT folder_id FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !$result['folder_id']) {
        return false;
    }
    
    $folderId = $result['folder_id'];
    $folderPath = getFolderPath($pdo, $folderId);
    
    // Check if the student has folder access to any folder in the path
    $placeholders = implode(',', array_fill(0, count($folderPath), '?'));
    $stmt = $pdo->prepare("
        SELECT 1 FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        WHERE e.student_id = ? 
        AND c.folder_id IN ($placeholders)
        AND e.is_folder_access = 1
    ");
    
    $params = array_merge([$studentId], $folderPath);
    $stmt->execute($params);
    
    return $stmt->rowCount() > 0;
}

/**
 * Get the path from a folder to the root (including the folder itself)
 */
function getFolderPath($pdo, $folderId) {
    $path = [$folderId];
    $currentId = $folderId;
    
    while ($currentId) {
        $stmt = $pdo->prepare("SELECT parent_id FROM folders WHERE id = ?");
        $stmt->execute([$currentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['parent_id'] === null) {
            break;
        }
        
        $currentId = $result['parent_id'];
        $path[] = $currentId;
    }
    
    return $path;
}