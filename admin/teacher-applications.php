<?php
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: /login.php');
    exit;
}

// Handle application approval/rejection/deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Replace the existing deletion code block with this:
if (isset($_POST['delete'])) {
    try {
        $pdo->beginTransaction();
        
        // First get the user_id from teacher_profiles
        $stmt = $pdo->prepare("SELECT user_id FROM teacher_profiles WHERE id = ?");
        $stmt->execute([$_POST['application_id']]);
        $user_id = $stmt->fetchColumn();
        
        // Delete from teacher_profiles
        $stmt = $pdo->prepare("DELETE FROM teacher_profiles WHERE id = ?");
        $stmt->execute([$_POST['application_id']]);
        
        // Delete from users
        if ($user_id) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        
        $pdo->commit();
        header('Location: teacher-applications.php?success=1&message=Application and user account deleted successfully');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        $error = "An error occurred while deleting the application.";
    }
} else if (isset($_POST['action'])) {
            // Handle status update
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

            header('Location: teacher-applications.php?success=1&message=Status updated successfully');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "An error occurred while processing the application.";
    }
}

// Fetch all applications with detailed information
try {
    $stmt = $pdo->query("
        SELECT 
            tp.*, 
            u.name, 
            u.email, 
            u.created_at as user_joined,
            u.phone,
            approver.name as approved_by_name,
            tp.address,
            tp.city,
            tp.state,
            tp.postal_code,
            tp.teaching_certifications,
            tp.linkedin_profile,
            tp.emergency_contact_phone
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
                    <p class="text-green-700"><?= htmlspecialchars($_GET['message'] ?? 'Operation completed successfully!') ?></p>
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
                                    <?php else: ?>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                            <select name="action" onchange="this.form.submit()" 
                                                    class="text-sm border-gray-300 rounded-md">
                                                <option value="pending" <?= $app['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="approved" <?= $app['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                                <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                    <button onclick='viewDetails(<?= json_encode($app) ?>)'
                                            class="text-indigo-600 hover:text-indigo-900 ml-2">
                                        View Details
                                    </button>
                                    <form method="POST" class="inline-block ml-2" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                        <input type="hidden" name="delete" value="1">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </form>
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
            
            const formatDate = (dateString) => {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            };
            
            content.innerHTML = `
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Contact Information</h4>
                        <p class="mt-1">Phone: ${application.phone || 'N/A'}</p>
                        <p>Emergency Contact: ${application.emergency_contact_phone || 'N/A'}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Address</h4>
                        <p class="mt-1">${application.address || 'N/A'}</p>
                        <p>${application.city || ''} ${application.state || ''} ${application.postal_code || ''}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Qualifications</h4>
                        <p class="mt-1">${application.qualification || 'N/A'}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Teaching Certifications</h4>
                        <p class="mt-1">${application.teaching_certifications || 'N/A'}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Expertise</h4>
                        <p class="mt-1">${application.expertise || 'N/A'}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Bio</h4>
                        <p class="mt-1">${application.bio || 'N/A'}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">LinkedIn Profile</h4>
                        <p class="mt-1">${application.linkedin_profile ? `<a href="${application.linkedin_profile}" target="_blank" class="text-indigo-600 hover:text-indigo-900">View Profile</a>` : 'N/A'}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Application Status</h4>
                        <p class="mt-1">
                            ${application.status.charAt(0).toUpperCase() + application.status.slice(1)}
                            ${application.approved_by_name ? `(by ${application.approved_by_name})` : ''}
                            on ${application.approved_at ? formatDate(application.approved_at) : 'N/A'}
                        </p>
                    </div>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function confirmDelete() {
            return confirm('Are you sure you want to delete this application? This action cannot be undone.');
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
                if (this.querySelector('input[name="delete"]')) {
                    return; // Skip for delete forms as they have their own confirmation
                }
                
                const action = this.querySelector('input[name="action"]')?.value;
                if (!action) return; // Skip for forms without action
                
                const message = action === 'approved' 
                    ? 'Are you sure you want to approve this teacher application?' 
                    : 'Are you sure you want to reject this teacher application?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>