<?php
require_once 'vendor/autoload.php';
$client = new Google_Client();
$client->setAuthConfig('client_secret.json');
$client->setRedirectUri('http://localhost/nimru-Web/generate_token.php');
$client->setScopes([
    'https://www.googleapis.com/auth/youtube.upload',
    'https://www.googleapis.com/auth/youtube'
]);

try {
    $code = '4/0AanRRrtjeR0ZTTm1fJvCM5YXDwZIYD6cZwHraTrFgPSKCjokPtVx5QSlvwNtfmrIjARYmQ';
    $accessToken = $client->fetchAccessTokenWithAuthCode($code);
    
    if (isset($accessToken['access_token'])) {
        $client->setAccessToken($accessToken);
        file_put_contents('youtube_token.json', json_encode($accessToken));
        echo "Token saved successfully";
    } else {
        echo "Error: No access token received";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}