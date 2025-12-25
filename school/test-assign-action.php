<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: test-assignment.php');
    exit;
}

$school_id = $_SESSION['user_id'];
$test_id = (int)$_POST['test_id'];
$student_ids = $_POST['student_ids'] ?? [];
$deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
$notes = trim($_POST['notes']);

// Validation
if ($test_id <= 0) {
    header('Location: test-assignment.php?tab=assign&error=invalid_test');
    exit;
}

if (empty($student_ids)) {
    header('Location: test-assignment.php?tab=assign&error=no_students');
    exit;
}

try {
    // Verify test exists
    $stmt = $conn->prepare("SELECT id FROM test_templates WHERE id = ?");
    $stmt->execute([$test_id]);
    if (!$stmt->fetch()) {
        header('Location: test-assignment.php?tab=assign&error=test_not_found');
        exit;
    }
    
    $assigned_count = 0;
    
    // Assign test to each selected student
    foreach ($student_ids as $student_id) {
        $student_id = (int)$student_id;
        
        // Verify student belongs to this school
        $stmt = $conn->prepare("
            SELECT student_id 
            FROM school_students 
            WHERE school_id = ? AND student_id = ? AND status = 'active'
        ");
        $stmt->execute([$school_id, $student_id]);
        
        if ($stmt->fetch()) {
            // Check if assignment already exists
            $stmt = $conn->prepare("
                SELECT id 
                FROM test_assignments 
                WHERE school_id = ? AND student_id = ? AND test_template_id = ?
            ");
            $stmt->execute([$school_id, $student_id, $test_id]);
            
            if (!$stmt->fetch()) {
                // Create new assignment
                $stmt = $conn->prepare("
                    INSERT INTO test_assignments 
                    (school_id, student_id, test_template_id, assigned_by, deadline, status, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([$school_id, $student_id, $test_id, $school_id, $deadline, $notes]);
                $assigned_count++;
            }
        }
    }
    
    header('Location: test-assignment.php?tab=assignments&success=assigned&count=' . $assigned_count);
    exit;
    
} catch (Exception $e) {
    error_log("Test assignment error: " . $e->getMessage());
    header('Location: test-assignment.php?tab=assign&error=assignment_failed');
    exit;
}
?>