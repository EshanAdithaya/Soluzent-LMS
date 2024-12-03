<?php
// youtube_api.php

require_once __DIR__ . '/../../vendor/autoload.php';

use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;

class YouTubeUploader {
    private $client;
    private $youtube;
    private $tokenPath;

    public function __construct() {
        $this->client = new Client();
        $this->tokenPath = __DIR__ . '/../../youtube_token.json';
        
        // Load credentials from downloaded JSON file
        $this->client->setAuthConfig(__DIR__ . '/../../client_secrets.json');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent'); // Force to get refresh token
        $this->client->setIncludeGrantedScopes(true);
        $this->client->addScope(YouTube::YOUTUBE_UPLOAD);
        
        // Load previously authorized token if it exists
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($this->client->isAccessTokenExpired()) {
            // Refresh the token if possible
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } else {
                // If no refresh token exists, start auth flow
                $authUrl = $this->client->createAuthUrl();
                header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
                exit;
            }
            
            // Save the token
            if (!file_exists(dirname($this->tokenPath))) {
                mkdir(dirname($this->tokenPath), 0700, true);
            }
            file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
        }
        
        $this->youtube = new YouTube($this->client);
    }

    public function uploadVideo($videoFile, $title, $description = '') {
        try {
            // Create a snippet with video information
            $snippet = new VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setCategoryId("27"); // Education category

            // Set video status to unlisted
            $status = new VideoStatus();
            $status->setPrivacyStatus("unlisted");

            // Create the video object
            $video = new Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // The upload process
            $response = $this->youtube->videos->insert(
                'snippet,status',
                $video,
                array(
                    'data' => file_get_contents($videoFile['tmp_name']),
                    'mimeType' => $videoFile['type'],
                    'uploadType' => 'multipart'
                )
            );

            return [
                'video_id' => $response->getId(),
                'embed_url' => "https://www.youtube.com/embed/" . $response->getId(),
                'watch_url' => "https://www.youtube.com/watch?v=" . $response->getId()
            ];
            
        } catch (Exception $e) {
            throw new Exception('YouTube API Error: ' . $e->getMessage());
        }
    }
}

// oauth2callback.php - Create this file in your project root
<?php
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/client_secrets.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php');
$client->addScope(Google\Service\YouTube::YOUTUBE_UPLOAD);

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
} else {
    $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $tokenPath = __DIR__ . '/youtube_token.json';
    
    if (!file_exists(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0700, true);
    }
    
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    header('Location: /admin/materials.php');
    exit;
}

// upload_video.php - Update this file
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../asset/php/config.php';
require_once __DIR__ . '/../asset/php/youtube_api.php';

try {
    if (!isset($_FILES['video'])) {
        throw new Exception('No video file uploaded');
    }

    // Check file size
    if ($_FILES['video']['size'] > 2147483648) {
        throw new Exception("File is too large. Maximum size is 2GB.");
    }

    // Check file type
    $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
    if (!in_array($_FILES['video']['type'], $allowedTypes)) {
        throw new Exception("Invalid video format. Allowed formats: MP4, MOV, AVI, WMV");
    }

    $uploader = new YouTubeUploader();
    $videoDetails = $uploader->uploadVideo(
        $_FILES['video'],
        $_POST['title'] ?? 'Uploaded Video',
        $_POST['description'] ?? ''
    );

    echo json_encode([
        'success' => true,
        'data' => $videoDetails
    ], JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
}
?>