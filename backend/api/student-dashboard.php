<?php
// api/student-dashboard.php

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    // Get student info
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Get enrolled classes
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.description
        FROM classes c
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.student_id = ?
    ');
    $stmt->execute([$userId]);
    $classes = $stmt->fetchAll();

    // Get recent materials
    $stmt = $pdo->prepare('
        SELECT m.id, m.title, m.type, m.content, c.name as class_name
        FROM materials m
        JOIN classes c ON m.class_id = c.id
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.student_id = ?
        ORDER BY m.created_at DESC
        LIMIT 5
    ');
    $stmt->execute([$userId]);
    $materials = $stmt->fetchAll();

    // Count total materials
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM materials m
        JOIN classes c ON m.class_id = c.id
        JOIN enrollments e ON c.id = e.class_id
        WHERE e.student_id = ?
    ');
    $stmt->execute([$userId]);
    $totalMaterials = $stmt->fetch()['total'];

    // Update last access time
    $stmt = $pdo->prepare('UPDATE users SET last_access = NOW() WHERE id = ?');
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'name' => $user['name'],
        'enrolledClasses' => $classes,
        'recentMaterials' => $materials,
        'totalMaterials' => $totalMaterials,
        'lastAccess' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log($e->getMessage());
}
?>