<?php
require_once '../config.php';

if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = getCurrentUser();
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetch()) {
        header('Location: profile.php?error=email_exists');
        exit;
    }
    
    // Update profile
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $phone, $user['id']]);
    
    // Update session
    $_SESSION['user_name'] = $full_name;
    
    header('Location: profile.php?success=1');
    exit;
}

header('Location: profile.php');
exit;
?>