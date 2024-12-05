<?php
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: /login.php');
    exit;
}

// Handle application approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE teacher_profiles 
            SET status = ?, approved_by = ?, approved_at = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['action'],
            $_SESSION['user_id'],
            $_POST['application_id']
        ]);

        if ($_POST['action'] === 'approved') {
            // Update user role to teacher
            $stmt = $pdo->prepare("
                UPDATE users 
                SET role = 'teacher' 
                WHERE id = (
                    SELECT user_id 
                    FROM teacher_profiles 
                    WHERE id = ?
                )
            ");
            $stmt->execute([$_POST['application_id']]);
        }

        header('Location: teacher-applications.php?success=1');
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "An error occurred while processing the application.";
    }
}

// Fetch all applications
try {
    $stmt = $pdo->query("
        SELECT tp.*, u.name, u.email, u.created_at as user_joined,
               approver.name as approved_by_name
        FROM teacher_profiles tp
        JOIN users u ON tp.user_id = u.id
        LEFT JOIN users approver ON tp.approved_by = approver.id
        ORDER BY tp.created_at DESC
    ");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $applications = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Applications - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'admin-header.php'; ?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-bold mb-6">Teacher Applications</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <p class="text-green-700">Application status updated successfully!</p>
                </div>
            <?php endif; ?>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applied On</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($app['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($app['email']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        echo match($app['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>">
                                        <?= ucfirst($app['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= date('M j, Y', strtotime($app['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form method="POST" class="inline-block mr-2">
                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="action" value="approved">
                                            <button type="submit" class="text-green-600 hover:text-green-900">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button onclick="viewDetails(<?= htmlspecialchars(json_encode($app)) ?>)"
                                            class="text-indigo-600 hover:text-indigo-900 ml-2">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Application Details</h3>
            <div id="modalContent" class="space-y-4">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeModal()"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function viewDetails(application) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            
            content.innerHTML = `
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Qualifications</h4>
                        <p class="mt-1">${application.qualification}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Expertise</h4>
                        <p class="mt-1">${application.expertise}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Bio</h4>
                        <p class="mt-1">${application.bio}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Application Status</h4>
                        <p class="mt-1">
                            ${application.status.charAt(0).toUpperCase() + application.status.slice(1)}
                            ${application.approved_by_name ? `(by ${application.approved_by_name})` : ''}
                        </p>
                    </div>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }


       // Close modal on outside click
       window.onclick = function(event) {
                const modal = document.getElementById('detailsModal');
                if (event.target === modal) {
                    closeModal();
                }
            }

            // Close modal on escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            // Confirm before approving/rejecting
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = this.querySelector('input[name="action"]').value;
                    const message = action === 'approved' 
                        ? 'Are you sure you want to approve this teacher application?' 
                        : 'Are you sure you want to reject this teacher application?';
                    
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });