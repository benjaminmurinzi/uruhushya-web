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
    // Update user status to inactive (or delete)
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Or you can delete the user entirely:
    // $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    // $stmt->execute([$user_id]);
    
    // TODO: Send email/SMS notification to user about rejection
    
    header('Location: requests.php?success=rejected');
    exit;
    
} catch (Exception $e) {
    error_log("Reject request error: " . $e->getMessage());
    header('Location: requests.php?error=reject_failed');
    exit;
}
?>