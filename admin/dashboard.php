<?php
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
require_once 'adminSession.php';

$title = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher' ? 'Teacher Dashboard' : 'Admin Dashboard';
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$is_dashboard_page = basename($_SERVER['PHP_SELF']) === 'dashboard.php';

try {
    if ($isTeacher) {
        // Student count query
        $stmt = $pdo->prepare('
            SELECT COUNT(DISTINCT s.id) 
            FROM users s 
            JOIN teacher_students ts ON s.id = ts.student_id 
            WHERE ts.teacher_id = :teacher_id AND ts.status = \'accepted\'
        ');
        if (!$stmt->execute(['teacher_id' => $_SESSION['user_id']])) {
            throw new Exception('Error executing student count query');
        }
        $totalStudents = $stmt->fetchColumn();
    
        // Classes count
        $stmt = $pdo->prepare('
            SELECT COUNT(*) 
            FROM classes c
            JOIN teacher_classes tc ON c.id = tc.class_id
            WHERE tc.teacher_id = :teacher_id
        ');
        if (!$stmt->execute(['teacher_id' => $_SESSION['user_id']])) {
            throw new Exception('Error executing class count query');
        }
        $activeClasses = $stmt->fetchColumn();
    
        // Materials count
        $stmt = $pdo->prepare('
            SELECT COUNT(*) 
            FROM materials m 
            JOIN classes c ON m.class_id = c.id
            JOIN teacher_classes tc ON c.id = tc.class_id
            WHERE tc.teacher_id = :teacher_id
        ');
        if (!$stmt->execute(['teacher_id' => $_SESSION['user_id']])) {
            throw new Exception('Error executing materials count query');
        }
        $totalMaterials = $stmt->fetchColumn();
    
        // New students count
        $stmt = $pdo->prepare('
            SELECT COUNT(DISTINCT s.id) 
            FROM users s 
            JOIN teacher_students ts ON s.id = ts.student_id 
            WHERE ts.teacher_id = :teacher_id 
            AND ts.status = \'accepted\'
            AND ts.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 WEEK)
        ');
        if (!$stmt->execute(['teacher_id' => $_SESSION['user_id']])) {
            throw new Exception('Error executing new students count query');
        }
        $newStudents = $stmt->fetchColumn();
    
        // Recent students
        $stmt = $pdo->prepare('
            SELECT 
                s.name, 
                s.email, 
                DATE_FORMAT(ts.created_at, \'%Y-%m-%d\') as joined_date
            FROM users s 
            JOIN teacher_students ts ON s.id = ts.student_id
            WHERE ts.teacher_id = :teacher_id 
            AND ts.status = \'accepted\'
            AND s.role = \'student\'
            ORDER BY ts.created_at DESC 
            LIMIT 5
        ');
        if (!$stmt->execute(['teacher_id' => $_SESSION['user_id']])) {
            throw new Exception('Error executing recent students query');
        }
        $recentStudents = $stmt->fetchAll();
    
        // Recent materials
        $stmt = $pdo->prepare('
            SELECT 
                m.title, 
                m.type, 
                c.name as class_name
            FROM materials m
            JOIN classes c ON m.class_id = c.id
            JOIN teacher_classes tc ON c.id = tc.class_id
            WHERE tc.teacher_id = :teacher_id
            ORDER BY m.created_at DESC
            LIMIT 5
        ');
        if (!$stmt->execute(['teacher_id' => $_SESSION['user_id']])) {
            throw new Exception('Error executing recent materials query');
        }
        $recentMaterials = $stmt->fetchAll();
    
    } else {
        // Admin queries (existing queries)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => 'student']);
        $totalStudents = $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM classes');
        $stmt->execute();
        $activeClasses = $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM materials');
        $stmt->execute();
        $totalMaterials = $stmt->fetchColumn();

        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM users 
            WHERE role = :role 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
        ');
        $stmt->execute(['role' => 'student']);
        $newStudents = $stmt->fetchColumn();

        $stmt = $pdo->prepare('
            SELECT name, email, DATE_FORMAT(created_at, \'%Y-%m-%d\') as joined_date
            FROM users 
            WHERE role = :role
            ORDER BY created_at DESC 
            LIMIT 5
        ');
        $stmt->execute(['role' => 'student']);
        $recentStudents = $stmt->fetchAll();

        $stmt = $pdo->prepare('
            SELECT m.title, m.type, c.name as class_name
            FROM materials m
            JOIN classes c ON m.class_id = c.id
            ORDER BY m.created_at DESC
            LIMIT 5
        ');
        $stmt->execute();
        $recentMaterials = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
?>

<?php require_once 'admin-header.php'; ?>
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Welcome Section -->
    <div class="px-4 py-5 sm:px-6">
        <?php if (!$is_dashboard_page): ?>
            <a href="<?php echo $admin_prefix; ?>dashboard.php">
                <h2 class="text-2xl font-bold text-gray-900"><?php echo $title; ?></h2>
            </a>
        <?php else: ?>
            <h2 class="text-2xl font-bold text-gray-900"><?php echo $title; ?></h2>
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <p class="mt-1 text-sm text-gray-600">
                <?php echo $isTeacher ? 'Overview of your teaching activities' : 'Overview of platform statistics'; ?>
            </p>
            <p class="text-lg text-gray-700">Welcome, 
                <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
            </p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">
                    <?php echo $isTeacher ? 'My Students' : 'Total Students'; ?>
                </dt>
                <dd id="totalStudents" class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo($totalStudents) ?>
                </dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">
                    <?php echo $isTeacher ? 'My Classes' : 'Active Classes'; ?>
                </dt>
                <dd id="activeClasses" class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo($activeClasses) ?>
                </dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">
                    <?php echo $isTeacher ? 'My Materials' : 'Total Materials'; ?>
                </dt>
                <dd id="totalMaterials" class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo($totalMaterials) ?>
                </dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500">
                    <?php echo $isTeacher ? 'New Students (This Week)' : 'New Students (This Week)'; ?>
                </dt>
                <dd id="newStudents" class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo($newStudents) ?>
                </dd>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Students -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900">
                    <?php echo $isTeacher ? 'My Recent Students' : 'Recent Students'; ?>
                </h3>
                <div class="mt-4" id="recentStudentsList">
                    <?php if (!empty($recentStudents)): ?>
                        <?php foreach ($recentStudents as $student): ?>
                            <div class="flex items-center justify-between py-3 border-b">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </p>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($student['joined_date']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="animate-pulse">No recent students available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Materials -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900">
                    <?php echo $isTeacher ? 'My Recent Materials' : 'Recent Materials'; ?>
                </h3>
                <div class="mt-4" id="recentMaterialsList">
                    <?php if (!empty($recentMaterials)): ?>
                        <?php foreach ($recentMaterials as $material): ?>
                            <div class="flex items-center justify-between py-3 border-b">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($material['title']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($material['class_name']); ?>
                                    </p>
                                </div>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    bg-<?php echo $material['type'] === 'pdf' ? 'red' : 'blue'; ?>-100 
                                    text-<?php echo $material['type'] === 'pdf' ? 'red' : 'blue'; ?>-800">
                                    <?php echo htmlspecialchars($material['type']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="animate-pulse">No recent materials available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>