<?php
require_once '../config.php';

if (!isset($_GET['code'])) {
    header('Location: ' . SITE_URL . '/?error=oauth_failed');
    exit;
}

$code = $_GET['code'];
$user_type = $_SESSION['oauth_user_type'] ?? 'student';

// Exchange code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_response = json_decode($response, true);

if (!isset($token_response['access_token'])) {
    header('Location: ' . SITE_URL . '/?error=token_failed');
    exit;
}

// Get user info from Google
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userinfo_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_response['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userinfo_response = curl_exec($ch);
curl_close($ch);

$userinfo = json_decode($userinfo_response, true);

if (!isset($userinfo['email'])) {
    header('Location: ' . SITE_URL . '/?error=userinfo_failed');
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
$stmt->execute([$userinfo['email'], $user_type]);
$user = $stmt->fetch();

if ($user) {
    // User exists - login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user_type;
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['last_activity'] = time();
} else {
    // Create new user
    $full_name = $userinfo['name'] ?? $userinfo['email'];
    $google_id = $userinfo['id'];
    
    $stmt = $conn->prepare("INSERT INTO users (user_type, full_name, email, google_id, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([$user_type, $full_name, $userinfo['email'], $google_id]);
    
    $user_id = $conn->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['last_activity'] = time();
}

// Redirect to appropriate dashboard
$redirect = [
    'student' => SITE_URL . '/student/dashboard.php',
    'school' => SITE_URL . '/school/dashboard.php',
    'agent' => SITE_URL . '/agent/dashboard.php'
];

header('Location: ' . $redirect[$user_type]);
exit;
?>