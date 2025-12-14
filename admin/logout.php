<?php
session_start();

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Redirect to homepage
header('Location: ' . SITE_URL . '/index.php?logged_out=1');
exit;
?>