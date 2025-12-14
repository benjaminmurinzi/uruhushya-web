<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($test_id <= 0) {
    header('Location: tests.php?error=invalid_test');
    exit;
}

try {
    // Get test details
    $stmt = $conn->prepare("SELECT id, test_code, total_questions FROM test_templates WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        header('Location: tests.php?error=test_not_found');
        exit;
    }
    
    // Get current question count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_questions WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $current_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $needed = $test['total_questions'] - $current_count;
    
    if ($needed <= 0) {
        header('Location: test-questions.php?id=' . $test_id . '&error=already_full');
        exit;
    }
    
    // Get random available questions
    $stmt = $conn->prepare("
        SELECT q.id 
        FROM questions q
        WHERE q.id NOT IN (
            SELECT question_id FROM test_questions WHERE test_template_id = ?
        )
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$test_id, $needed]);
    $available_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($available_questions)) {
        header('Location: test-questions.php?id=' . $test_id . '&error=no_questions');
        exit;
    }
    
    // Get next question order
    $stmt = $conn->prepare("SELECT MAX(question_order) as max_order FROM test_questions WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_order = ($result['max_order'] ?? 0) + 1;
    
    // Add questions
    $added_count = 0;
    foreach ($available_questions as $question) {
        $stmt = $conn->prepare("INSERT INTO test_questions (test_template_id, question_id, question_order) VALUES (?, ?, ?)");
        $stmt->execute([$test_id, $question['id'], $next_order]);
        $next_order++;
        $added_count++;
    }
    
    header('Location: test-questions.php?id=' . $test_id . '&success=auto_assigned&count=' . $added_count);
    exit;
    
} catch (Exception $e) {
    error_log("Auto-assign questions error: " . $e->getMessage());
    header('Location: test-questions.php?id=' . $test_id . '&error=auto_assign_failed');
    exit;
}
?>