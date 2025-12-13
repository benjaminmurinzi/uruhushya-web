<?php
require_once '../config.php';

// Store user type in session for callback
$_SESSION['oauth_user_type'] = $_GET['type'] ?? 'student';

// Google OAuth URL
$google_oauth_url = 'https://accounts.google.com/o/oauth2/v2/auth';

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'offline',
    'prompt' => 'consent'
];

$auth_url = $google_oauth_url . '?' . http_build_query($params);

header('Location: ' . $auth_url);
exit;
?>