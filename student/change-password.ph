<?php
require_once '../config.php';

if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = getCurrentUser();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        header('Location: profile.php?error=wrong_password');
        exit;
    }
    
    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        header('Location: profile.php?error=password_mismatch');
        exit;
    }
    
    // Update password
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $user['id']]);
    
    header('Location: profile.php?success=password_changed');
    exit;
}

header('Location: profile.php');
exit;
?>