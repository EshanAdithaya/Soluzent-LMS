<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../asset/php/config.php';
require_once __DIR__ . '/../asset/php/youtube_api.php';

try {
    // Log the received request
    error_log("Video upload request received");
    error_log("FILES: " . print_r($_FILES, true));
    error_log("POST: " . print_r($_POST, true));

    if (!isset($_FILES['video'])) {
        throw new Exception('No video file uploaded');
    }

    // Check file size (2GB limit)
    if ($_FILES['video']['size'] > 2147483648) {
        throw new Exception("File is too large. Maximum size is 2GB.");
    }

    // Check file type
    $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
    if (!in_array($_FILES['video']['type'], $allowedTypes)) {
        throw new Exception("Invalid video format. Allowed formats: MP4, MOV, AVI, WMV");
    }

    // Check for upload errors
    if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        );
        $errorMessage = isset($uploadErrors[$_FILES['video']['error']]) 
            ? $uploadErrors[$_FILES['video']['error']] 
            : "Unknown upload error";
        throw new Exception($errorMessage);
    }

    $uploader = new YouTubeUploader();
    error_log("YouTubeUploader instance created");

    $videoDetails = $uploader->uploadVideo(
        $_FILES['video'],
        $_POST['title'] ?? 'Uploaded Video',
        $_POST['description'] ?? ''
    );
    error_log("Video uploaded successfully: " . print_r($videoDetails, true));

    // Send success response
    echo json_encode([
        'success' => true,
        'data' => $videoDetails
    ], JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
}
?>