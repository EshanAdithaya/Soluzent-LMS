<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once '../asset/php/config.php';
require_once 'adminSession.php';
require_once 'youtube_api.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['video'])) {
        throw new Exception('No video file uploaded');
    }

    $uploader = new YouTubeUploader();
    $result = $uploader->uploadVideo(
        $_FILES['video'],
        $_POST['title'] ?? 'Untitled Video'
    );

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}