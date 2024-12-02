<?php
session_start();
require_once '../asset/php/config.php';
require_once '../asset/php/db.php';
require_once '../asset/php/youtube_api.php';

// Check admin authentication
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit;
// }

// Handle file uploads
function handleFileUpload($file) {
    $targetDir = "../uploads/materials/";
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

// Handle video upload and generate embed URL
function handleVideoUrl($url) {
    // Extract YouTube video ID from different URL formats
    $videoId = '';
    
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        $videoId = $match[1];
    }
    
    if (!$videoId) {
        throw new Exception("Invalid YouTube URL");
    }
    
    return [
        'video_id' => $videoId,
        'embed_url' => "https://www.youtube.com/embed/" . $videoId,
        'watch_url' => $url
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        switch ($_POST['action']) {
            case 'create':
                switch ($_POST['materialType']) {
                    case 'video':
                        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== 0) {
                            throw new Exception('Video file is required.');
                        }
                    
                        // Check file size (2GB limit for YouTube)
                        if ($_FILES['video']['size'] > 2147483648) {
                            throw new Exception("File is too large. Maximum size is 2GB.");
                        }
                    
                        // Check file type
                        $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
                        if (!in_array($_FILES['video']['type'], $allowedTypes)) {
                            throw new Exception("Invalid video format. Allowed formats: MP4, MOV, AVI, WMV");
                        }
                    
                        try {
                            $uploader = new YouTubeUploader();
                            $videoDetails = $uploader->uploadVideo(
                                $_FILES['video'],
                                $_POST['title'],
                                $_POST['description'] ?? ''
                            );
                    
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
                                $videoDetails['watch_url'],
                                $videoDetails['video_id'],
                                $videoDetails['embed_url']
                            ]);
                        } catch (Exception $e) {
                            throw new Exception("Failed to upload video: " . $e->getMessage());
                        }
                        break;

                    case 'link':
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
                        break;

                    case 'file':
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
                        break;
                }
                $_SESSION['success'] = "Material added successfully.";
                break;

            case 'update':
                $type = $_POST['materialType'];
                $content = $_POST['content'] ?? '';

                if ($type === 'video') {
                    if (!empty($_POST['videoUrl'])) {
                        $videoDetails = handleVideoUrl($_POST['videoUrl']);
                        $stmt = $pdo->prepare("
                            UPDATE materials 
                            SET title = ?, type = ?, content = ?, video_id = ?, embed_url = ?, class_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $_POST['title'],
                            'video',
                            $videoDetails['watch_url'],
                            $videoDetails['video_id'],
                            $videoDetails['embed_url'],
                            $_POST['class_id'],
                            $_POST['material_id']
                        ]);
                    }
                } else if ($type === 'file' && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                    // Delete old file
                    $stmt = $pdo->prepare("SELECT content, type FROM materials WHERE id = ?");
                    $stmt->execute([$_POST['material_id']]);
                    $oldMaterial = $stmt->fetch();
                    
                    if ($oldMaterial['type'] != 'link' && $oldMaterial['type'] != 'video') {
                        $oldFile = "../uploads/materials/" . $oldMaterial['content'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    $fileName = handleFileUpload($_FILES['file']);
                    $content = $fileName;
                }

                $stmt = $pdo->prepare("
                    UPDATE materials 
                    SET title = ?, class_id = ?" . 
                    ($type !== 'video' ? ", type = ?, content = ?" : "") . "
                    WHERE id = ?
                ");

                $params = [$_POST['title'], $_POST['class_id']];
                if ($type !== 'video') {
                    $params[] = $_POST['type'] ?? $type;
                    $params[] = $content;
                }
                $params[] = $_POST['material_id'];
                
                $stmt->execute($params);
                $_SESSION['success'] = "Material updated successfully.";
                break;

            case 'delete':
                // Get file info before deletion
                $stmt = $pdo->prepare("SELECT content, type FROM materials WHERE id = ?");
                $stmt->execute([$_POST['material_id']]);
                $material = $stmt->fetch();

                // Delete file if exists
                if ($material['type'] != 'link' && $material['type'] != 'video') {
                    $file = "../uploads/materials/" . $material['content'];
                    if (file_exists($file)) {
                        unlink($file);
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
                                <a href="../uploads/materials/<?= htmlspecialchars($material['content']) ?>" 
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
    <label for="video" class="block text-sm font-medium text-gray-700">Video File</label>
    <input type="file" name="video" id="video" accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
           class="mt-1 block w-full text-sm text-gray-500
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-md file:border-0
                  file:text-sm file:font-semibold
                  file:bg-indigo-50 file:text-indigo-700
                  hover:file:bg-indigo-100">
    <p class="mt-1 text-sm text-gray-500">
        Maximum file size: 2GB. Supported formats: MP4, MOV, AVI, WMV
    </p>
    <div class="mt-4">
        <label for="description" class="block text-sm font-medium text-gray-700">Video Description (Optional)</label>
        <textarea name="description" id="description" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
    </div>
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
        function validateForm() {
            const materialType = document.querySelector('input[name="materialType"]:checked').value;
            
            if (materialType === 'video') {
                const videoUrl = document.getElementById('videoUrl').value.trim();
                if (!videoUrl) {
                    alert('Please enter a YouTube URL');
                    return false;
                }
                if (!videoUrl.match(/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/)) {
                    alert('Please enter a valid YouTube URL');
                    return false;
                }
            }
            
            return true;
        }

        function toggleMaterialType(type) {
            const linkInput = document.getElementById('linkInput');
            const fileInput = document.getElementById('fileInput');
            const videoInput = document.getElementById('videoInput');
            
            linkInput.classList.add('hidden');
            fileInput.classList.add('hidden');
            videoInput.classList.add('hidden');
            
            document.getElementById('content').required = false;
            document.getElementById('file').required = false;
            document.getElementById('videoUrl').required = false;
            
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
                    document.getElementById('videoUrl').required = true;
                    break;
            }
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Material';
            document.getElementById('formAction').value = 'create';
            document.getElementById('materialId').value = '';
            document.getElementById('materialForm').reset();
            document.querySelector('input[name="materialType"][value="link"]').checked = true;
            toggleMaterialType('link');
            document.getElementById('materialModal').classList.remove('hidden');
        }

        function openEditModal(material) {
            document.getElementById('modalTitle').textContent = 'Edit Material';
            document.getElementById('formAction').value = 'update';
            document.getElementById('materialId').value = material.id;
            document.getElementById('classId').value = material.class_id;
            document.getElementById('title').value = material.title;

            // Set material type
            document.querySelector(`input[name="materialType"][value="${material.type}"]`).checked = true;
            
            if (material.type === 'video') {
                document.getElementById('videoUrl').value = material.content;
            } else if (material.type === 'link') {
                document.getElementById('content').value = material.content;
            }
            
            toggleMaterialType(material.type);
            document.getElementById('materialModal').classList.remove('hidden');
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

        // Form validation
        document.getElementById('materialForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>