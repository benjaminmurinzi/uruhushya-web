<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$school_id = $_SESSION['user_id'];
$school_student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($school_student_id <= 0) {
    header('Location: students.php?error=invalid_student');
    exit;
}

try {
    // Verify this student belongs to this school
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM school_students 
        WHERE id = ? AND school_id = ?
    ");
    $stmt->execute([$school_student_id, $school_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        header('Location: students.php?error=student_not_found');
        exit;
    }
    
    // Delete from school_students (this unlinks student from school, but keeps user account)
    $stmt = $conn->prepare("DELETE FROM school_students WHERE id = ?");
    $stmt->execute([$school_student_id]);
    
    header('Location: students.php?success=deleted');
    exit;
    
} catch (Exception $e) {
    error_log("Delete student error: " . $e->getMessage());
    header('Location: students.php?error=delete_failed');
    exit;
}
?>