<?php
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
include_once 'admin-header.php';
require_once 'adminSession.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO team_members (name, position, bio, display_order, social_linkedin, social_twitter, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['position'],
                        $_POST['bio'],
                        $_POST['display_order'],
                        $_POST['social_linkedin'],
                        $_POST['social_twitter'],
                        $_SESSION['user_id']
                    ]);
                    break;

                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE team_members SET 
                            name = ?,
                            position = ?,
                            bio = ?,
                            display_order = ?,
                            social_linkedin = ?,
                            social_twitter = ?,
                            is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['position'],
                        $_POST['bio'],
                        $_POST['display_order'],
                        $_POST['social_linkedin'],
                        $_POST['social_twitter'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['member_id']
                    ]);
                    break;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
                    $stmt->execute([$_POST['member_id']]);
                    break;
            }
        }
        
        $pdo->commit();
        $success_message = "Team members updated successfully!";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        $error_message = "Error updating team members: " . $e->getMessage();
    }
}

// Get team members
$stmt = $pdo->prepare("SELECT * FROM team_members ORDER BY display_order, name");
$stmt->execute();
$team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team Members - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Manage Team Members</h1>
                <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Add Member
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Team Members List -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($team_members as $member): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($member['image_path']): ?>
                                            <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($member['image_path']); ?>" alt="">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($member['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($member['position']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($member['display_order']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $member['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="editMember(<?php echo htmlspecialchars(json_encode($member)); ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMember(<?php echo $member['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    
        <!-- Add/Edit Modal -->
        <div id="memberModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h2 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add Team Member</h2>
                    <form id="memberForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="member_id" id="memberId">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="memberName" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Position</label>
                                <input type="text" name="position" id="memberPosition" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bio</label>
                                <textarea name="bio" id="memberBio" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Display Order</label>
                                <input type="number" name="display_order" id="memberOrder" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
    
                            <div>
                                <label class="block text-sm font-medium text-gray-700">LinkedIn Profile</label>
                                <input type="url" name="social_linkedin" id="memberLinkedIn"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
    
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Twitter Profile</label>
                                <input type="url" name="social_twitter" id="memberTwitter"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
    
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" id="memberStatus" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">Active</span>
                                </label>
                            </div>
                        </div>
    
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeModal()"
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                            <button type="submit"
                                class="bg-indigo-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
        <script>
            function openAddModal() {
                document.getElementById('modalTitle').textContent = 'Add Team Member';
                document.getElementById('formAction').value = 'add';
                document.getElementById('memberForm').reset();
                document.getElementById('memberModal').classList.remove('hidden');
            }
    
            function editMember(member) {
                document.getElementById('modalTitle').textContent = 'Edit Team Member';
                document.getElementById('formAction').value = 'update';
                document.getElementById('memberId').value = member.id;
                document.getElementById('memberName').value = member.name;
                document.getElementById('memberPosition').value = member.position;
                document.getElementById('memberBio').value = member.bio;
                document.getElementById('memberOrder').value = member.display_order;
                document.getElementById('memberLinkedIn').value = member.social_linkedin;
                document.getElementById('memberTwitter').value = member.social_twitter;
                document.getElementById('memberStatus').checked = member.is_active == 1;
                document.getElementById('memberModal').classList.remove('hidden');
            }
    
            function closeModal() {
                document.getElementById('memberModal').classList.add('hidden');
            }
    
            function deleteMember(id) {
                if (confirm('Are you sure you want to delete this team member?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="member_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
    
        <?php include_once '../includes/admin_footer.php'; ?>
    </body>
    </html>