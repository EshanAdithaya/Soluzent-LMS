<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once '../asset/php/config.php';
require_once 'adminSession.php';

// Disable error reporting for JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

class VideoUploadHandler {
    private $youtubeUploader;
    private $maxFileSize = 2147483648; // 2GB in bytes
    private $allowedTypes = [
        'video/mp4',
        'video/quicktime',  // MOV
        'video/x-msvideo',  // AVI
        'video/x-ms-wmv'    // WMV
    ];

    public function __construct() {
        try {
            $this->youtubeUploader = new YouTubeUploader();
        } catch (Exception $e) {
            throw new Exception('Failed to initialize YouTube uploader: ' . $e->getMessage());
        }
    }

    private function validateUpload($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload failed');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of 2GB');
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: MP4, MOV, AVI, WMV');
        }
    }

    private function sanitizeTitle($title) {
        $title = trim($title);
        return empty($title) ? 'Untitled Video' : htmlspecialchars($title);
    }

    public function handleUpload() {
        try {
            // Validate session
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not authenticated');
            }

            // Validate and process upload
            $this->validateUpload($_FILES['video']);
            
            // Prepare video data
            $title = $this->sanitizeTitle($_POST['title'] ?? '');
            
            // Upload to YouTube
            $result = $this->youtubeUploader->uploadVideo(
                $_FILES['video'],
                $title,
                "Uploaded via " . APP_NAME
            );

            // Return success response
            return [
                'success' => true,
                'data' => [
                    'url' => $result['url'],
                    'id' => $result['id'],
                    'title' => $title
                ]
            ];

        } catch (Exception $e) {
            http_response_code(400);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Execute the upload
try {
    $handler = new VideoUploadHandler();
    $response = $handler->handleUpload();
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'System error: ' . $e->getMessage()
    ];
    http_response_code(500);
}

echo json_encode($response);
exit;