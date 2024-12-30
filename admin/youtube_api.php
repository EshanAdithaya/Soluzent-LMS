<?php
require_once __DIR__ . '/vendor/autoload.php';

class YouTubeUploader {
    private $client;
    private $youtube;

    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(__DIR__ . '/client_secret.json');
        $this->client->setScopes([
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube'
        ]);
        $this->client->setAccessType('offline');
        
        $tokenPath = __DIR__ . '/youtube_token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }
        
        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($this->client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
        }
        
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    public function uploadVideo($file, $title, $description = '') {
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
                    'mimeType' => $file['type']
                )
            );
            
            return [
                'id' => $response->getId(),
                'url' => "https://www.youtube.com/watch?v=" . $response->getId()
            ];
        } catch (Google_Service_Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
?>