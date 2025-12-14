<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: requests.php');
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($user_id <= 0) {
    header('Location: requests.php?error=invalid_user');
    exit;
}

try {
    // Update user status to active
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // TODO: Send email/SMS notification to user about approval
    
    header('Location: requests.php?success=approved');
    exit;
    
} catch (Exception $e) {
    error_log("Approve request error: " . $e->getMessage());
    header('Location: requests.php?error=approve_failed');
    exit;
}
?>