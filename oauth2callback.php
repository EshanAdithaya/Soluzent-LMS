<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

try {
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/client_secrets.json');
    
    // Set the redirect URI to match your application's callback URL
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
    $client->setRedirectUri($redirect_uri);
    
    // Add required scopes
    $client->addScope('https://www.googleapis.com/auth/youtube.upload');
    $client->addScope('https://www.googleapis.com/auth/youtube');
    
    // State parameter to prevent request forgery attacks
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
        unset($_SESSION['oauth2state']);
        exit('Invalid state parameter.');
    }

    // Handle the OAuth 2.0 server response
    if (isset($_GET['code'])) {
        // Exchange authorization code for an access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception($token['error_description']);
        }
        
        // Store the token
        $tokenPath = __DIR__ . '/youtube_token.json';
        
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        
        file_put_contents($tokenPath, json_encode($token));
        
        // Redirect back to the materials page
        header('Location: /admin/materials.php');
        exit;
    }

} catch (Exception $e) {
    // Log error and display user-friendly message
    error_log('OAuth error: ' . $e->getMessage());
    echo 'An error occurred during authentication. Please try again or contact support.';
    exit;
}

// If we don't have a code yet, generate the authorization URL
if (!isset($_GET['code'])) {
    // Generate a random state parameter
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth2state'] = $state;
    
    $client->setState($state);
    $authUrl = $client->createAuthUrl();
    
    // Redirect to Google's OAuth 2.0 server
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Authentication</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Authentication in Progress</h1>
        <p class="text-gray-600">
            Please wait while we complete the authentication process...
        </p>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                <div class="bg-indigo-600 h-2.5 rounded-full w-full animate-pulse"></div>
            </div>
        </div>
    </div>
</body>
</html>