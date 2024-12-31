<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

class YouTubeUploader {
   private $client;
   private $youtube;
   private $tokenPath;
   private $clientSecretPath;

   public function __construct() {
       $this->tokenPath = dirname(__DIR__) . '/youtube_token.json';
       $this->clientSecretPath = dirname(__DIR__) . '/client_secret.json';

       if (!file_exists($this->clientSecretPath)) {
           throw new Exception('client_secret.json not found');
       }

       $this->initializeClient();
   }

   private function initializeClient() {
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
   }

   private function validateAndSetToken() {
       if (!file_exists($this->tokenPath)) {
           $this->handleInitialAuth();
           return;
       }

       $token = json_decode(file_get_contents($this->tokenPath), true);
       if (!$this->isValidToken($token)) {
           throw new Exception('Invalid token format');
       }

       $this->client->setAccessToken($token);
       if ($this->client->isAccessTokenExpired()) {
           if ($this->client->getRefreshToken()) {
               try {
                   $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                   file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
               } catch (Exception $e) {
                   $this->handleInitialAuth();
               }
           } else {
               $this->handleInitialAuth();
           }
       }
   }

   private function isValidToken($token) {
       return is_array($token) && 
              isset($token['access_token']) && 
              isset($token['token_type']);
   }

   private function handleInitialAuth() {
       if (isset($_GET['code'])) {
           try {
               $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
               if (isset($token['access_token'])) {
                   file_put_contents($this->tokenPath, json_encode($token));
               } else {
                   throw new Exception('No access token received');
               }
           } catch (Exception $e) {
               throw new Exception('Authorization failed: ' . $e->getMessage());
           }
       } else {
           $authUrl = $this->client->createAuthUrl();
           header('Location: ' . $authUrl);
           exit;
       }
   }

   public function uploadVideo($file, $title, $description = '') {
       if (!file_exists($file['tmp_name'])) {
           throw new Exception('Video file not found');
       }

       $snippet = new Google_Service_YouTube_VideoSnippet();
       $snippet->setTitle($title);
       $snippet->setDescription($description);

       $status = new Google_Service_YouTube_VideoStatus();
       $status->setPrivacyStatus('unlisted');

       $video = new Google_Service_YouTube_Video();
       $video->setSnippet($snippet);
       $video->setStatus($status);

       try {
           $response = $this->youtube->videos->insert(
               'snippet,status',
               $video,
               array(
                   'data' => file_get_contents($file['tmp_name']),
                   'mimeType' => $file['type'],
                   'uploadType' => 'multipart'
               )
           );

           return [
               'success' => true,
               'id' => $response->getId(),
               'url' => "https://www.youtube.com/watch?v=" . $response->getId()
           ];
       } catch (Google_Service_Exception $e) {
           throw new Exception('Upload failed: ' . $e->getMessage());
       }
   }
}