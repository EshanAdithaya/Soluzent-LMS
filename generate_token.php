<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('client_secret.json');
$client->setScopes([
    'https://www.googleapis.com/auth/youtube.upload',
    'https://www.googleapis.com/auth/youtube'
]);

$authUrl = $client->createAuthUrl();
echo "Open this URL: " . $authUrl . "\n";

// After authorization, you'll get a code. Enter it below:
$authCode = readline('Enter code: ');

$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
file_put_contents('youtube_token.json', json_encode($accessToken));