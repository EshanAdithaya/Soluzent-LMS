<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Service_Exception;
use Exception;

class YouTubeUploader {
    private $client;
    private $youtube;
    private $tokenPath;
    private $clientSecretPath;

    public function __construct() {
        $this->tokenPath = dirname(__DIR__) . '/youtube_token.json';
        $this->clientSecretPath = dirname(__DIR__) . '/client_secret.json';

        if (!file_exists($this->clientSecretPath)) {
            throw new Exception('YouTube API credentials not found: client_secret.json is missing');
        }

        $this->initializeClient();
    }

    private function initializeClient() {
        try {
            $this->client = new Google_Client();
            $this->client->setAuthConfig($this->clientSecretPath);
            $this->client->setRedirectUri('http://localhost/nimru-Web/generate_token.php');
            $this->client->setScopes([
                'https://www.googleapis.com/auth/youtube.upload',
                'https://www.googleapis.com/auth/youtube'
            ]);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');

            $this->validateAndSetToken();
            $this->youtube = new Google_Service_YouTube($this->client);
        } catch (Exception $e) {
            error_log("YouTube client initialization failed: " . $e->getMessage());
            throw new Exception('Failed to initialize YouTube client: ' . $e->getMessage());
        }
    }

    private function validateAndSetToken() {
        try {
            if (!file_exists($this->tokenPath)) {
                error_log("Token file not found at: " . $this->tokenPath);
                $this->handleInitialAuth();
                return;
            }

            $tokenContent = file_get_contents($this->tokenPath);
            if ($tokenContent === false) {
                throw new Exception("Could not read token file");
            }

            $token = json_decode($tokenContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Token file contains invalid JSON: " . json_last_error_msg());
            }

            if (!$this->isValidToken($token)) {
                throw new Exception('Invalid token format in token file');
            }

            $this->client->setAccessToken($token);
            
            if ($this->client->isAccessTokenExpired()) {
                if (!$this->client->getRefreshToken()) {
                    error_log("No refresh token available, initiating new auth flow");
                    $this->handleInitialAuth();
                    return;
                }

                error_log("Access token expired, attempting refresh");
                $this->refreshAccessToken();
            }
        } catch (Exception $e) {
            error_log("Token validation failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function refreshAccessToken() {
        try {
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            $newToken = $this->client->getAccessToken();
            
            if (!$this->isValidToken($newToken)) {
                throw new Exception('Received invalid token during refresh');
            }
            
            if (file_put_contents($this->tokenPath, json_encode($newToken)) === false) {
                throw new Exception('Failed to save refreshed token');
            }
        } catch (Exception $e) {
            error_log("Token refresh failed: " . $e->getMessage());
            $this->handleInitialAuth();
        }
    }

    private function isValidToken($token) {
        return is_array($token) && 
               isset($token['access_token']) && 
               isset($token['token_type']) &&
               !empty($token['access_token']) &&
               !empty($token['token_type']);
    }

    private function handleInitialAuth() {
        if (!isset($_GET['code'])) {
            $authUrl = $this->client->createAuthUrl();
            error_log("Redirecting to auth URL: " . $authUrl);
            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit;
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            if (!isset($token['access_token'])) {
                throw new Exception('No access token received from Google');
            }

            if (file_put_contents($this->tokenPath, json_encode($token)) === false) {
                throw new Exception('Failed to save new token');
            }

            $this->client->setAccessToken($token);
        } catch (Exception $e) {
            error_log("Initial authorization failed: " . $e->getMessage());
            throw new Exception('Authorization failed: ' . $e->getMessage());
        }
    }

    public function uploadVideo($file, $title, $description = '') {
        try {
            if (!file_exists($file['tmp_name'])) {
                throw new Exception('Video file not found at temporary location');
            }

            $fileContent = file_get_contents($file['tmp_name']);
            if ($fileContent === false) {
                throw new Exception('Failed to read video file');
            }

            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);

            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus('unlisted');

            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            error_log("Attempting to upload video: " . $title);
            
            $response = $this->youtube->videos->insert(
                'snippet,status',
                $video,
                array(
                    'data' => $fileContent,
                    'mimeType' => $file['type'],
                    'uploadType' => 'multipart'
                )
            );

            if (!$response || !$response->getId()) {
                throw new Exception('Upload completed but no video ID received');
            }

            error_log("Video upload successful. ID: " . $response->getId());
            
            return [
                'success' => true,
                'id' => $response->getId(),
                'url' => "https://www.youtube.com/watch?v=" . $response->getId(),
                'debug_info' => [
                    'file_size' => strlen($fileContent),
                    'mime_type' => $file['type'],
                    'response' => json_encode($response)
                ]
            ];
        } catch (Google_Service_Exception $e) {
            $errorBody = $e->getMessage();
            error_log("YouTube API error (raw): " . $errorBody);
            
            try {
                $errorData = json_decode($errorBody, true);
                $errorMessage = isset($errorData['error']['message']) 
                    ? $errorData['error']['message'] 
                    : $errorBody;
            } catch (Exception $jsonError) {
                $errorMessage = $errorBody;
            }
                
            error_log("YouTube API error (processed): " . $errorMessage);
            throw new Exception('YouTube API error: ' . $errorMessage);
        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            throw new Exception('Upload failed: ' . $e->getMessage());
        }
    }
}