<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once '../asset/php/config.php';
require_once 'adminSession.php';

// Enable error logging to a file
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/upload_errors.log');

// Still disable display errors for clean JSON output
ini_set('display_errors', 0);

// Set content type
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
            // Log initialization
            error_log("Initializing VideoUploadHandler");
            
            if (!class_exists('YouTubeUploader')) {
                throw new Exception('YouTubeUploader class not found');
            }
            
            $this->youtubeUploader = new YouTubeUploader();
            error_log("YouTubeUploader initialized successfully");
        } catch (Exception $e) {
            error_log("Constructor error: " . $e->getMessage());
            throw new Exception('Failed to initialize YouTube uploader: ' . $e->getMessage());
        }
    }

    private function validateUpload($file) {
        error_log("Validating upload: " . json_encode($file));
        
        if (!isset($file) || !is_array($file)) {
            throw new Exception('No file data received');
        }

        if (!isset($file['error'])) {
            throw new Exception('Invalid file upload structure');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
            ];
            $errorMessage = isset($uploadErrors[$file['error']]) 
                ? $uploadErrors[$file['error']] 
                : 'Unknown upload error';
            throw new Exception($errorMessage);
        }

        if (!file_exists($file['tmp_name'])) {
            throw new Exception('Uploaded file not found in temporary directory');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of 2GB');
        }

        $mimeType = mime_content_type($file['tmp_name']);
        error_log("File mime type: " . $mimeType);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: MP4, MOV, AVI, WMV');
        }
    }

    public function handleUpload() {
        try {
            error_log("Starting upload handling");
            
            // Check if files were uploaded
            if (!isset($_FILES['video'])) {
                error_log("No video file in request");
                throw new Exception('No video file uploaded');
            }

            // Validate session
            if (!isset($_SESSION['user_id'])) {
                error_log("No user session");
                throw new Exception('User not authenticated');
            }

            error_log("Processing upload for user: " . $_SESSION['user_id']);

            // Validate and process upload
            $this->validateUpload($_FILES['video']);
            
            // Prepare video data
            $title = isset($_POST['title']) ? trim($_POST['title']) : 'Untitled Video';
            $title = htmlspecialchars($title);
            
            error_log("Uploading video with title: " . $title);

            // Upload to YouTube
            $result = $this->youtubeUploader->uploadVideo(
                $_FILES['video'],
                $title,
                "Uploaded via " . APP_NAME
            );

            error_log("Upload successful. Result: " . json_encode($result));

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
            error_log("Upload handler error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

try {
    error_log("Starting video upload process");
    
    $handler = new VideoUploadHandler();
    $response = $handler->handleUpload();
    
    error_log("Final response: " . json_encode($response));
    
    if (!is_array($response)) {
        throw new Exception('Invalid response format');
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Fatal error in upload process: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => 'System error: ' . $e->getMessage()
    ];
    
    http_response_code(500);
    echo json_encode($response);
}

// Ensure no additional output
exit;