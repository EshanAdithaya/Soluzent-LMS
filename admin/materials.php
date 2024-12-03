<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../asset/php/php-error.log');
session_start();

require_once __DIR__ . '/../asset/php/config.php';

// Handle file uploads
function handleFileUpload($file) {
    error_log("handleFileUpload called");
    $targetDir = "../asset/uploads/materials/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check file size (5MB limit for regular files)
    if ($file["size"] > 5000000) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    // Allow certain file formats
    $allowedTypes = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Only PDF, DOC, DOCX, JPG, JPEG & PNG files are allowed.");
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $fileName;
    }
    
    throw new Exception("Failed to upload file.");
}

// Helper function to extract video ID from YouTube URL
function getYoutubeVideoId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    throw new Exception("Invalid YouTube URL format.");
}

// Handle form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        error_log("Transaction started");

        switch ($_POST['action']) {
            case 'create':
                error_log("Create action initiated");
                switch ($_POST['materialType']) {
                    case 'video':
                        error_log("Video material type selected");
                        $videoId = getYoutubeVideoId($_POST['youtube_url']);
                        $embedUrl = "https://www.youtube.com/embed/" . $videoId;
                        $watchUrl = "https://www.youtube.com/watch?v=" . $videoId;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO materials (
                                class_id, 
                                title, 
                                type, 
                                content,
                                video_id,
                                embed_url
                            ) VALUES (?, ?, 'video', ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['class_id'],
                            $_POST['title'],
                            $watchUrl,
                            $videoId,
                            $embedUrl
                        ]);
                        error_log("Video material inserted into database");
                        break;

                    case 'link':
                        error_log("Link material type selected");
                        if (empty($_POST['content'])) {
                            throw new Exception('URL is required.');
                        }
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO materials (class_id, title, type, content) 
                            VALUES (?, ?, 'link', ?)
                        ");
                        $stmt->execute([
                            $_POST['class_id'],
                            $_POST['title'],
                            $_POST['content']
                        ]);
                        error_log("Link material inserted into database");
                        break;

                    case 'file':
                        error_log("File material type selected");
                        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
                            throw new Exception('File is required.');
                        }

                        $fileName = handleFileUpload($_FILES['file']);
                        $stmt = $pdo->prepare("
                            INSERT INTO materials (class_id, title, type, content) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['class_id'],
                            $_POST['title'],
                            $_POST['type'],
                            $fileName
                        ]);
                        error_log("File material inserted into database");
                        break;
                }
                $_SESSION['success'] = "Material added successfully.";
                break;

            case 'update':
                error_log("Update action initiated");
                $type = $_POST['materialType'];
                $content = $_POST['content'] ?? '';

                if ($type === 'video') {
                    $videoId = getYoutubeVideoId($_POST['youtube_url']);
                    $embedUrl = "https://www.youtube.com/embed/" . $videoId;
                    $watchUrl = "https://www.youtube.com/watch?v=" . $videoId;
                    
                    $stmt = $pdo->prepare("
                        UPDATE materials 
                        SET title = ?, class_id = ?, type = ?, content = ?, video_id = ?, embed_url = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['class_id'],
                        $type,
                        $watchUrl,
                        $videoId,
                        $embedUrl,
                        $_POST['material_id']
                    ]);
                } else {
                    // Handle file update if new file is uploaded
                    if ($type === 'file' && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                        // Delete old file
                        $stmt = $pdo->prepare("SELECT content FROM materials WHERE id = ?");
                        $stmt->execute([$_POST['material_id']]);
                        $oldFile = $stmt->fetchColumn();
                        
                        $oldFilePath = "../asset/uploads/materials/" . $oldFile;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }

                        $content = handleFileUpload($_FILES['file']);
                    }

                    $stmt = $pdo->prepare("
                        UPDATE materials 
                        SET title = ?, class_id = ?, type = ?, content = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['class_id'],
                        $type,
                        $content,
                        $_POST['material_id']
                    ]);
                }
                error_log("Material updated in database");
                $_SESSION['success'] = "Material updated successfully.";
                break;

            case 'delete':
                error_log("Delete action initiated");
                // Get file info before deletion
                $stmt = $pdo->prepare("SELECT content, type FROM materials WHERE id = ?");
                $stmt->execute([$_POST['material_id']]);
                $material = $stmt->fetch();

                // Delete physical file if exists
                if ($material['type'] === 'file') {
                    $filePath = "../asset/uploads/materials/" . $material['content'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
                $stmt->execute([$_POST['material_id']]);
                error_log("Material deleted from database");
                $_SESSION['success'] = "Material deleted successfully.";
                break;
        }

        $pdo->commit();
        error_log("Transaction committed");
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: materials.php');
    exit;
}

// Fetch all materials with class names
$stmt = $pdo->query("
    SELECT m.*, c.name as class_name 
    FROM materials m 
    JOIN classes c ON m.class_id = c.id 
    ORDER BY m.created_at DESC
");
$materials = $stmt->fetchAll();

// Fetch all classes for the dropdown
$stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name");
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-indigo-600">EduPortal Admin</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Dashboard</a>
                    <a href="classes.php" class="text-gray-500 hover:text-gray-700">Classes</a>
                    <a href="students.php" class="text-gray-500 hover:text-gray-700">Students</a>
                    <a href="materials.php" class="text-gray-900 border-b-2 border-indigo-500">Materials</a>
                    <a href="../logout.php" class="text-gray-500 hover:text-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

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
            <h2 class="text-2xl font-bold text-gray-900">Materials Management</h2>
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
                            <?php if ($material['type'] === 'link'): ?>
                                <a href="<?= htmlspecialchars($material['content']) ?>" 
                                   target="_blank" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    View Link
                                </a>
                            <?php elseif ($material['type'] === 'video'): ?>
                                <button onclick="playVideo('<?= htmlspecialchars($material['embed_url']) ?>', <?= $material['id'] ?>)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Play Video
                                </button>
                            <?php else: ?>
                                <a href="../asset/uploads/materials/<?= htmlspecialchars($material['content']) ?>" 
                                   target="_blank"
                                   class="text-indigo-600 hover:text-indigo-900">
                                    View File
                                </a>
                            <?php endif; ?>
                        </td>
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
                        <div class="mt-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="type" value="pdf" checked
                                       class="form-radio text-indigo-600">
                                <span class="ml-2">PDF</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input type="radio" name="type" value="image"
                                       class="form-radio text-indigo-600">
                                <span class="ml-2">Image</span>
                            </label>
                        </div>
                    </div>
                    <div id="videoInput" class="hidden">
                        <label for="youtube_url" class="block text-sm font-medium text-gray-700">YouTube Video URL</label>
                        <input type="url" name="youtube_url" id="youtube_url"
                               placeholder="https://www.youtube.com/watch?v=..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-sm text-gray-500">
                            Enter the URL of your unlisted YouTube video
                        </p>
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

    <!-- Video Player Modal -->
    <div id="videoPlayer" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="relative bg-white rounded-lg max-w-4xl w-full mx-4">
            <div class="absolute top-0 right-0 p-4">
                <button onclick="closeVideoPlayer()" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="aspect-w-16 aspect-h-9">
                <iframe id="youtubeEmbed" 
                        class="w-full h-full" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            </div>
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

            document.querySelector(`input[name="materialType"][value="${material.type}"]`).checked = true;
            toggleMaterialType(material.type);
            
            if (material.type === 'link') {
                document.getElementById('content').value = material.content;
            } else if (material.type === 'video') {
                document.getElementById('youtube_url').value = material.content;
            }
        }

        function closeModal() {
            document.getElementById('materialModal').classList.add('hidden');
            document.getElementById('materialForm').reset();
        }

        function playVideo(embedUrl, materialId) {
            document.getElementById('youtubeEmbed').src = embedUrl;
            document.getElementById('videoPlayer').classList.remove('hidden');
        }

        function closeVideoPlayer() {
            document.getElementById('youtubeEmbed').src = '';
            document.getElementById('videoPlayer').classList.add('hidden');
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

        // Form submission handler
        document.getElementById('materialForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const materialType = document.querySelector('input[name="materialType"]:checked').value;
            let isValid = true;
            let errorMessage = '';

            if (!document.getElementById('title').value.trim()) {
                errorMessage = 'Please enter a title';
                isValid = false;
            } else if (materialType === 'link' && !document.getElementById('content').value.trim()) {
                errorMessage = 'Please enter a URL';
                isValid = false;
            } else if (materialType === 'file' && !document.getElementById('file').files[0]) {
                errorMessage = 'Please select a file';
                isValid = false;
            } else if (materialType === 'video' && !document.getElementById('youtube_url').value.trim()) {
                errorMessage = 'Please enter a YouTube video URL';
                isValid = false;
            }

            if (!isValid) {
                alert(errorMessage);
                return;
            }

            this.submit();
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target === document.getElementById('materialModal')) {
                closeModal();
            }
            if (event.target === document.getElementById('videoPlayer')) {
                closeVideoPlayer();
            }
        }

        // Handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeVideoPlayer();
            }
        });
    </script>
</body>
</html>