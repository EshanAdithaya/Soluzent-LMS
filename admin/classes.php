<?php
/**
 * Main Classes & Folders Management page
 * This is the primary view for teachers and admins to manage the class and folder structure
 */
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
require_once 'adminSession.php';

// Include controller and model files
require_once 'folder-class-controller.php';
require_once 'folder-class-model.php';

// First, check if the classes table has the folder_id column and add it if not
ensureFolderIdColumn($pdo);

// Fetch all folders with hierarchy
$hierarchicalFolders = getAllFolders($pdo);

// Get all classes with their folder information
$classes = getAllClasses($pdo);

// Get students that can be enrolled
$students = getEnrollableStudents($pdo);

// Group classes by folder
$classesByFolder = groupClassesByFolder($classes);
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
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="max-w-7xl mx-auto mt-4 mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="max-w-7xl mx-auto mt-4 mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
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
                <button onclick="openAddModal('class')" data-type="class" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Add New Class
                </button>
                <button onclick="openAddModal('folder')" data-type="folder" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
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
                                            <button onclick="viewFolderStudents(<?= $folder['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                                View Access
                                            </button>
                                            <button onclick="openFolderEnrollModal(<?= $folder['id'] ?>)" class="text-green-600 hover:text-green-900">
                                                Manage Access
                                            </button>
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
                        displayFolderAndClassesHierarchy($hierarchicalFolders, $classesByFolder);
                        
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
    <div id="folderModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-10">
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
    <div id="classModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-10">
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

    <!-- Enroll Students to Class Modal -->
    <div id="enrollModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-10">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Enroll Students</h3>
            <form method="POST">
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="class_id" id="enrollClassId">
                
                <div class="mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_folder_access" id="isFolderAccess" 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="isFolderAccess" class="ml-2 block text-sm text-gray-700">
                            Give folder-level access (student will have access to all classes in the folder)
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

    <!-- Folder Enrollment Modal -->
    <div id="folderEnrollModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-10">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Manage Folder Access</h3>
            <p class="text-sm text-gray-600 mb-4">
                When you enroll students to a folder, they'll gain access to all classes within this folder and its subfolders.
            </p>
            <form method="POST" id="folderEnrollForm">
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="class_id" id="enrollFolderId">
                <input type="hidden" name="is_folder_access" value="1">
                
                <div class="space-y-4">
                    <input type="text" id="folderStudentSearch" placeholder="Search students..." 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <div id="folderStudentList" class="max-h-96 overflow-y-auto space-y-4">
                        <?php foreach ($students as $student): ?>
                        <div class="flex items-center folder-student-item">
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
                    <button type="button" onclick="closeModal('folderEnrollModal')"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Update Folder Access
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Students Modal -->
    <div id="viewStudentsModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-10">
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
    <!-- JavaScript section at the bottom of classes.php -->
    <script>
        // Ensure all JavaScript runs after the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Button click handlers for opening modals
            document.querySelectorAll('[onclick^="openAddModal"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent any default action
                    const type = this.getAttribute('data-type') || 'class';
                    openAddModal(type);
                });
            });
            
            document.querySelectorAll('[onclick^="openEditFolderModal"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const folderData = JSON.parse(this.getAttribute('data-folder'));
                    openEditFolderModal(folderData);
                });
            });
            
            document.querySelectorAll('[onclick^="openEditClassModal"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const classData = JSON.parse(this.getAttribute('data-class'));
                    openEditClassModal(classData);
                });
            });
            
            document.querySelectorAll('[onclick^="openEnrollModal"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const classId = this.getAttribute('data-class-id');
                    openEnrollModal(classId);
                });
            });
            
            document.querySelectorAll('[onclick^="openFolderEnrollModal"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const folderId = this.getAttribute('data-folder-id');
                    openFolderEnrollModal(folderId);
                });
            });
            
            document.querySelectorAll('[onclick^="viewStudents"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const classId = this.getAttribute('data-class-id');
                    viewStudents(classId);
                });
            });
            
            document.querySelectorAll('[onclick^="viewFolderStudents"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const folderId = this.getAttribute('data-folder-id');
                    viewFolderStudents(folderId);
                });
            });
            
            document.querySelectorAll('[onclick^="confirmDeleteFolder"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const folderId = this.getAttribute('data-folder-id');
                    confirmDeleteFolder(folderId);
                });
            });
            
            document.querySelectorAll('[onclick^="confirmDeleteClass"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const classId = this.getAttribute('data-class-id');
                    confirmDeleteClass(classId);
                });
            });
            
            // Modal close buttons
            document.querySelectorAll('[onclick^="closeModal"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modalId = this.getAttribute('data-modal-id');
                    closeModal(modalId);
                });
            });
            
            // Define function implementations
            function openAddModal(type) {
                console.log('Opening add modal for:', type);
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
                console.log('Opening edit folder modal for:', folder);
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
                console.log('Opening edit class modal for:', classObj);
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
                console.log('Opening enroll modal for class ID:', classId);
                document.getElementById('enrollClassId').value = classId;
                document.getElementById('isFolderAccess').checked = false;
                document.getElementById('enrollModal').classList.remove('hidden');
                
                // Reset all checkboxes
                const checkboxes = document.querySelectorAll('#studentList input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
            
            function openFolderEnrollModal(folderId) {
                console.log('Opening folder enroll modal for folder ID:', folderId);
                document.getElementById('enrollFolderId').value = folderId;
                document.getElementById('folderEnrollModal').classList.remove('hidden');
                
                // Reset all checkboxes
                const checkboxes = document.querySelectorAll('#folderStudentList input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }

            async function viewStudents(classId) {
                console.log('Viewing students for class ID:', classId);
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
            
            async function viewFolderStudents(folderId) {
                console.log('Viewing students for folder ID:', folderId);
                try {
                    const response = await fetch(`?action=get_folder_students&folder_id=${folderId}`);
                    const data = await response.json();
                    
                    const studentsList = document.getElementById('enrolledStudentsList');
                    studentsList.innerHTML = data.students.map(student => `
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <div>
                                <span>${student.name} (${student.email})</span>
                                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Folder Access</span>
                            </div>
                            <button onclick="unenrollFolderStudent(${folderId}, ${student.id})" 
                                    class="text-red-600 hover:text-red-900 text-sm">
                                Remove
                            </button>
                        </div>
                    `).join('') || '<p class="text-gray-500">No students have folder-level access</p>';
                    
                    document.getElementById('viewStudentsModal').classList.remove('hidden');
                } catch (error) {
                    console.error('Error:', error);
                }
            }

            function closeModal(modalId) {
                console.log('Closing modal:', modalId);
                document.getElementById(modalId).classList.add('hidden');
            }

            function confirmDeleteFolder(id) {
                console.log('Confirming folder deletion for ID:', id);
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
                console.log('Confirming class deletion for ID:', id);
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
                console.log('Unenrolling student ID:', studentId, 'from class ID:', classId);
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
            
            async function unenrollFolderStudent(folderId, studentId) {
                console.log('Unenrolling student ID:', studentId, 'from folder ID:', folderId);
                if (confirm('Are you sure you want to remove this student\'s folder access?')) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'unenroll_from_folder');
                        formData.append('folder_id', folderId);
                        formData.append('student_id', studentId);

                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });

                        if (response.ok) {
                            // Refresh the students list
                            viewFolderStudents(folderId);

                            // Refresh the page to update
                            location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error removing student\'s folder access');
                    }
                }
            }

            // Close modals when clicking outside
            window.onclick = function(event) {
                const modals = ['folderModal', 'classModal', 'enrollModal', 'viewStudentsModal', 'folderEnrollModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            };

            // Handle escape key to close modals
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const modals = ['folderModal', 'classModal', 'enrollModal', 'viewStudentsModal', 'folderEnrollModal'];
                    modals.forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (!modal.classList.contains('hidden')) {
                            closeModal(modalId);
                        }
                    });
                }
            });

            // Make sure these functions are available globally
            window.openAddModal = openAddModal;
            window.openEditFolderModal = openEditFolderModal;
            window.openEditClassModal = openEditClassModal;
            window.openEnrollModal = openEnrollModal;
            window.openFolderEnrollModal = openFolderEnrollModal;
            window.viewStudents = viewStudents;
            window.viewFolderStudents = viewFolderStudents;
            window.closeModal = closeModal;
            window.confirmDeleteFolder = confirmDeleteFolder;
            window.confirmDeleteClass = confirmDeleteClass;
            window.unenrollStudent = unenrollStudent;
            window.unenrollFolderStudent = unenrollFolderStudent;

            // Setup form event handlers
            if (document.querySelector('#enrollModal form')) {
                document.querySelector('#enrollModal form').addEventListener('submit', function(e) {
                    const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one student to enroll');
                    }
                });
            }
            
            if (document.querySelector('#folderEnrollForm')) {
                document.querySelector('#folderEnrollForm').addEventListener('submit', function(e) {
                    const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one student to update folder access');
                    }
                });
            }

            // Setup search functionality
            if (document.getElementById('studentSearch')) {
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
            }
            
            if (document.getElementById('folderStudentSearch')) {
                document.getElementById('folderStudentSearch').addEventListener('input', function() {
                    const searchValue = this.value.toLowerCase();
                    const studentItems = document.querySelectorAll('#folderStudentList .folder-student-item');
                    studentItems.forEach(item => {
                        const studentName = item.querySelector('label').textContent.toLowerCase();
                        if (studentName.includes(searchValue)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            console.log('All event handlers initialized');
        });
    </script>
</body>
</html>