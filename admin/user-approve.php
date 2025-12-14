<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    try {
        // Update user status to active
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Get user details for notification (optional)
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // TODO: Send email/SMS notification to user about approval
        
        // Redirect back with success message
        header('Location: users.php?success=approved&name=' . urlencode($user['full_name']));
        exit;
        
    } catch (Exception $e) {
        error_log("User approval error: " . $e->getMessage());
        header('Location: users.php?error=approval_failed');
        exit;
    }
} else {
    header('Location: users.php?error=invalid_user');
    exit;
}
?>