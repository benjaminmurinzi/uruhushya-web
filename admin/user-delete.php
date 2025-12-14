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
        // Check if user is admin (prevent deleting admin accounts)
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['user_type'] === 'admin') {
            header('Location: users.php?error=cannot_delete_admin');
            exit;
        }
        
        // Delete related data first (foreign key constraints)
        // Delete student test attempts
        $stmt = $conn->prepare("DELETE FROM student_test_attempts WHERE student_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete student answers
        $stmt = $conn->prepare("DELETE FROM student_answers WHERE student_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        header('Location: users.php?success=deleted');
        exit;
        
    } catch (Exception $e) {
        error_log("User deletion error: " . $e->getMessage());
        header('Location: users.php?error=delete_failed');
        exit;
    }
} else {
    header('Location: users.php?error=invalid_user');
    exit;
}
?>