<?php
require_once __DIR__ . '/../asset/php/config.php';
require_once '../asset/php/db.php';


$isTeacher = $_SESSION['role'] === 'teacher';

// Handle file uploads
function handleFileUpload($file) {
    error_log("handleFileUpload called");
    $targetDir = UPLOAD_PATH . '/materials/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimeType = mime_content_type($file["tmp_name"]);

    // Check file size
    if ($file["size"] > MAX_FILE_SIZE) {
        throw new Exception("File is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.");
    }

    // Check file type
    if (!array_key_exists($fileType, ALLOWED_FILE_TYPES) || 
        !in_array($mimeType, ALLOWED_FILE_TYPES)) {
        throw new Exception("Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, JPEG & PNG.");
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $fileName;
    }
    
    throw new Exception("Failed to upload file.");
}

// When saving form (in the POST handler):
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        switch ($_POST['action']) {
            case 'create':
                switch ($_POST['materialType']) {
                    case 'video':
                        $stmt = $pdo->prepare("
                            INSERT INTO materials (class_id, title, type, content, added_by) 
                            VALUES (?, ?, 'youtubeLink', ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['class_id'],
                            $_POST['title'],
                            $_POST['youtube_url'],
                            $_SESSION['user_id']
                        ]);
                        break;

                    case 'link':
                        $stmt = $pdo->prepare("
                            INSERT INTO materials (class_id, title, type, content, added_by) 
                            VALUES (?, ?, 'link', ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['class_id'],
                            $_POST['title'],
                            $_POST['content'],
                            $_SESSION['user_id']
                        ]);
                        break;

                    case 'file':
                        if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                            $fileName = handleFileUpload($_FILES['file']);
                            $stmt = $pdo->prepare("
                                INSERT INTO materials (class_id, title, type, content, added_by) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $_POST['class_id'],
                                $_POST['title'],
                                $_POST['type'],
                                $fileName,
                                $_SESSION['user_id']
                            ]);
                        }
                        break;
                }
                $_SESSION['success'] = "Material added successfully.";
                break;

            case 'update':
                // Verify ownership if teacher
                if ($isTeacher) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM materials 
                        WHERE id = ? AND added_by = ?
                    ");
                    $stmt->execute([$_POST['material_id'], $_SESSION['user_id']]);
                    if ($stmt->fetchColumn() == 0) {
                        throw new Exception("You don't have permission to edit this material.");
                    }
                }

                $type = $_POST['materialType'];
                $content = $_POST['content'] ?? '';

                if ($type === 'video') {
                    $content = $_POST['youtube_url'];
                } elseif ($type === 'file' && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                    $content = handleFileUpload($_FILES['file']);
                }

                $stmt = $pdo->prepare("
                    UPDATE materials 
                    SET title = ?, class_id = ?, type = ?, content = ?, added_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['class_id'],
                    $type,
                    $content,
                    $_SESSION['user_id'],
                    $_POST['material_id']
                ]);
                $_SESSION['success'] = "Material updated successfully.";
                break;

            case 'delete':
                // Verify ownership if teacher
                if ($isTeacher) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM materials 
                        WHERE id = ? AND added_by = ?
                    ");
                    $stmt->execute([$_POST['material_id'], $_SESSION['user_id']]);
                    if ($stmt->fetchColumn() == 0) {
                        throw new Exception("You don't have permission to delete this material.");
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
                $stmt->execute([$_POST['material_id']]);
                $_SESSION['success'] = "Material deleted successfully.";
                break;
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch materials based on role
if ($isTeacher) {
    // Teachers can only see their own materials
    $stmt = $pdo->prepare("
        SELECT m.*, c.name as class_name, u.name as added_by_name 
        FROM materials m 
        JOIN classes c ON m.class_id = c.id 
        LEFT JOIN users u ON m.added_by = u.id
        WHERE m.added_by = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    // Admins can see all materials
    $stmt = $pdo->query("
        SELECT m.*, c.name as class_name, u.name as added_by_name 
        FROM materials m 
        JOIN classes c ON m.class_id = c.id 
        LEFT JOIN users u ON m.added_by = u.id
        ORDER BY m.created_at DESC
    ");
}
$materials = $stmt->fetchAll();

// Fetch available classes based on role
if ($isTeacher) {
    $stmt = $pdo->prepare("
        SELECT c.* 
        FROM classes c
        JOIN teacher_classes tc ON c.id = tc.class_id
        WHERE tc.teacher_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name");
}
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isTeacher ? 'My Materials' : 'Materials Management' ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once 'admin-header.php';?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Messages -->
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

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                <?= $isTeacher ? 'My Materials' : 'Materials Management' ?>
            </h2>
            <button onclick="openAddModal()" 
                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add New Material
            </button>
        </div>

        <!-- Materials List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                        <?php if (!$isTeacher): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($materials as $material): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($material['title']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($material['class_name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= ucfirst(htmlspecialchars($material['type'])) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php if ($material['type'] === 'link' || $material['type'] === 'video'): ?>
                                <a href="<?= htmlspecialchars($material['content']) ?>" 
                                   target="_blank" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    View <?= ucfirst($material['type']) ?>
                                </a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($material['content']) ?>" 
                                   target="_blank"
                                   class="text-indigo-600 hover:text-indigo-900">
                                    View File
                                </a>
                            <?php endif; ?>
                        </td>
                        <?php if (!$isTeacher): ?>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($material['added_by_name'] ?? 'Unknown') ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick='openEditModal(<?= json_encode($material) ?>)'
                                    class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button onclick="confirmDelete(<?= $material['id'] ?>)"
                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Material Modal -->
    <div id="materialModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <form id="materialForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="material_id" id="materialId">
                
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Material</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                        <select name="class_id" id="classId" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" id="title" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Material Type</label>
                        <div class="mt-2 space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="materialType" value="link" checked
                                       onchange="toggleMaterialType(this.value)"
                                       class="form-radio text-indigo-600">
                                <span class="ml-2">Link</span>
                            </label>
                            <label class="inline-flex items-center">
                            <input type="radio" name="materialType" value="file"
                                       onchange="toggleMaterialType(this.value)"
                                       class="form-radio text-indigo-600">
                                <span class="ml-2">File Upload</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="materialType" value="video"
                                       onchange="toggleMaterialType(this.value)"
                                       class="form-radio text-indigo-600">
                                <span class="ml-2">YouTube Video</span>
                            </label>
                        </div>
                    </div>
                    <div id="linkInput">
                        <label for="content" class="block text-sm font-medium text-gray-700">URL</label>
                        <input type="url" name="content" id="content"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div id="fileInput" class="hidden">
                        <label for="file" class="block text-sm font-medium text-gray-700">File</label>
                        <input type="file" name="file" id="file"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                        <input type="hidden" name="type" id="fileType" value="pdf">
                        <p class="mt-1 text-sm text-gray-500">
                            Maximum file size: <?= MAX_FILE_SIZE / 1024 / 1024 ?>MB
                        </p>
                    </div>
                    <div id="videoInput" class="hidden">
                        <label for="youtube_url" class="block text-sm font-medium text-gray-700">YouTube Video URL</label>
                        <input type="url" name="youtube_url" id="youtube_url"
                               placeholder="https://www.youtube.com/watch?v=..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
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

    <script>
        function toggleMaterialType(type) {
            const linkInput = document.getElementById('linkInput');
            const fileInput = document.getElementById('fileInput');
            const videoInput = document.getElementById('videoInput');
            
            linkInput.classList.add('hidden');
            fileInput.classList.add('hidden');
            videoInput.classList.add('hidden');
            
            document.getElementById('content').required = false;
            document.getElementById('file').required = false;
            document.getElementById('youtube_url').required = false;
            
            switch(type) {
                case 'link':
                    linkInput.classList.remove('hidden');
                    document.getElementById('content').required = true;
                    break;
                case 'file':
                    fileInput.classList.remove('hidden');
                    document.getElementById('file').required = true;
                    break;
                case 'video':
                    videoInput.classList.remove('hidden');
                    document.getElementById('youtube_url').required = true;
                    break;
            }
        }

        function openAddModal() {
            document.getElementById('materialModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Add New Material';
            document.getElementById('formAction').value = 'create';
            document.getElementById('materialId').value = '';
            document.getElementById('materialForm').reset();
            
            document.querySelector('input[name="materialType"][value="link"]').checked = true;
            toggleMaterialType('link');
        }

        function openEditModal(material) {
            document.getElementById('materialModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Edit Material';
            document.getElementById('formAction').value = 'update';
            document.getElementById('materialId').value = material.id;
            document.getElementById('classId').value = material.class_id;
            document.getElementById('title').value = material.title;

            document.querySelector(`input[name="materialType"][value="${material.type === 'youtubeLink' ? 'video' : material.type}"]`).checked = true;
            toggleMaterialType(material.type === 'youtubeLink' ? 'video' : material.type);
            
            if (material.type === 'link') {
                document.getElementById('content').value = material.content;
            } else if (material.type === 'youtubeLink') {
                document.getElementById('youtube_url').value = material.content;
            }
        }

        function closeModal() {
            document.getElementById('materialModal').classList.add('hidden');
            document.getElementById('materialForm').reset();
        }

        function confirmDelete(materialId) {
            if (confirm('Are you sure you want to delete this material? This cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="material_id" value="${materialId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === document.getElementById('materialModal')) {
                closeModal();
            }
        }

        // Handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Form validation
        document.getElementById('materialForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const materialType = document.querySelector('input[name="materialType"]:checked').value;
            
            if (!title) {
                e.preventDefault();
                alert('Please enter a title');
                return;
            }

            switch (materialType) {
                case 'link':
                    if (!document.getElementById('content').value.trim()) {
                        e.preventDefault();
                        alert('Please enter a URL');
                    }
                    break;
                case 'file':
                    const formAction = document.getElementById('formAction').value;
                    if (formAction === 'create' && !document.getElementById('file').files[0]) {
                        e.preventDefault();
                        alert('Please select a file');
                    }
                    break;
                case 'video':
                    if (!document.getElementById('youtube_url').value.trim()) {
                        e.preventDefault();
                        alert('Please enter a YouTube video URL');
                    }
                    break;
            }
        });
    </script>
</body>
</html>