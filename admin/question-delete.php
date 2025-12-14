<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($question_id > 0) {
    try {
        // Get question details (for image deletion)
        $stmt = $conn->prepare("SELECT question_image FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($question) {
            // Delete question from test_questions
            $stmt = $conn->prepare("DELETE FROM test_questions WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // Delete the question
            $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            
            // Delete image file if exists
            if (!empty($question['question_image']) && file_exists('../' . $question['question_image'])) {
                unlink('../' . $question['question_image']);
            }
            
            header('Location: questions.php?success=deleted');
            exit;
        } else {
            header('Location: questions.php?error=question_not_found');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Question deletion error: " . $e->getMessage());
        header('Location: questions.php?error=delete_failed');
        exit;
    }
} else {
    header('Location: questions.php?error=invalid_question');
    exit;
}
?>