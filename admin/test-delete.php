<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($test_id > 0) {
    try {
        // Delete test questions first (foreign key)
        $stmt = $conn->prepare("DELETE FROM test_questions WHERE test_template_id = ?");
        $stmt->execute([$test_id]);
        
        // Delete student test attempts
        $stmt = $conn->prepare("DELETE FROM student_test_attempts WHERE test_template_id = ?");
        $stmt->execute([$test_id]);
        
        // Delete student answers
        $stmt = $conn->prepare("
            DELETE sa FROM student_answers sa
            INNER JOIN student_test_attempts sta ON sa.attempt_id = sta.id
            WHERE sta.test_template_id = ?
        ");
        $stmt->execute([$test_id]);
        
        // Delete the test
        $stmt = $conn->prepare("DELETE FROM test_templates WHERE id = ?");
        $stmt->execute([$test_id]);
        
        header('Location: tests.php?success=deleted');
        exit;
        
    } catch (Exception $e) {
        error_log("Test deletion error: " . $e->getMessage());
        header('Location: tests.php?error=delete_failed');
        exit;
    }
} else {
    header('Location: tests.php?error=invalid_test');
    exit;
}
?>