<?php
require_once __DIR__ . '/vendor/autoload.php';

class YouTubeUploader {
    private $client;
    private $youtube;

    public function __construct() {
        $this->client = new Google_Client();
        
        // Load credentials from downloaded JSON file
        $this->client->setAuthConfig('client_secrets.json');
        $this->client->setAccessType('offline');
        $this->client->setScopes([
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube'
        ]);
        
        // Set up service
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    public function uploadVideo($videoFile, $title, $description = '') {
        try {
            // Create a snippet with video information
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setCategoryId("27"); // Education category

            // Set video status to unlisted
            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus("unlisted");

            // Create the video resource
            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Upload the video
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
                'success' => true,
                'video_id' => $response->getId(),
                'embed_url' => "https://www.youtube.com/embed/" . $response->getId(),
                'watch_url' => "https://www.youtube.com/watch?v=" . $response->getId()
            ];
            
        } catch (Google_Service_Exception $e) {
            throw new Exception('YouTube API Error: ' . $e->getMessage());
        } catch (Google_Exception $e) {
            throw new Exception('Client Error: ' . $e->getMessage());
        }
    }
}
?>