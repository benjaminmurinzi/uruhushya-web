<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tests.php');
    exit;
}

$test_id = isset($_POST['test_id']) ? (int)$_POST['test_id'] : 0;
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;

if ($test_id <= 0 || $question_id <= 0) {
    header('Location: tests.php?error=invalid_data');
    exit;
}

try {
    // Check if test exists
    $stmt = $conn->prepare("SELECT id, total_questions FROM test_templates WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        header('Location: tests.php?error=test_not_found');
        exit;
    }
    
    // Check current question count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_questions WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $current_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($current_count >= $test['total_questions']) {
        header('Location: test-questions.php?id=' . $test_id . '&error=max_questions');
        exit;
    }
    
    // Check if question is already assigned
    $stmt = $conn->prepare("SELECT id FROM test_questions WHERE test_template_id = ? AND question_id = ?");
    $stmt->execute([$test_id, $question_id]);
    if ($stmt->fetch()) {
        header('Location: test-questions.php?id=' . $test_id . '&error=already_assigned');
        exit;
    }
    
    // Get next question order
    $stmt = $conn->prepare("SELECT MAX(question_order) as max_order FROM test_questions WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_order = ($result['max_order'] ?? 0) + 1;
    
    // Add question to test
    $stmt = $conn->prepare("INSERT INTO test_questions (test_template_id, question_id, question_order) VALUES (?, ?, ?)");
    $stmt->execute([$test_id, $question_id, $next_order]);
    
    header('Location: test-questions.php?id=' . $test_id . '&success=added');
    exit;
    
} catch (Exception $e) {
    error_log("Add question to test error: " . $e->getMessage());
    header('Location: test-questions.php?id=' . $test_id . '&error=add_failed');
    exit;
}
?>