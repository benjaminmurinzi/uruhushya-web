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
    // Remove question from test
    $stmt = $conn->prepare("DELETE FROM test_questions WHERE test_template_id = ? AND question_id = ?");
    $stmt->execute([$test_id, $question_id]);
    
    // Reorder remaining questions
    $stmt = $conn->prepare("
        SELECT id 
        FROM test_questions 
        WHERE test_template_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $order = 1;
    foreach ($questions as $question) {
        $update_stmt = $conn->prepare("UPDATE test_questions SET question_order = ? WHERE id = ?");
        $update_stmt->execute([$order, $question['id']]);
        $order++;
    }
    
    header('Location: test-questions.php?id=' . $test_id . '&success=removed');
    exit;
    
} catch (Exception $e) {
    error_log("Remove question from test error: " . $e->getMessage());
    header('Location: test-questions.php?id=' . $test_id . '&error=remove_failed');
    exit;
}
?>