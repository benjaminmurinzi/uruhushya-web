<?php
require_once '../config.php';
require_once '../includes/language.php';

// Check login
if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$user = getCurrentUser();
$test_id = (int)$_POST['test_id'];
$time_taken = isset($_POST['time_taken']) ? (int)$_POST['time_taken'] : 0;

// Get test details
$stmt = $conn->prepare("SELECT * FROM test_templates WHERE id = ?");
$stmt->execute([$test_id]);
$test = $stmt->fetch();

if (!$test) {
    header('Location: dashboard.php?error=invalid_test');
    exit;
}

// Get all questions for this test
$stmt = $conn->prepare("
    SELECT q.*, tq.question_order 
    FROM test_questions tq
    JOIN questions q ON tq.question_id = q.id
    WHERE tq.test_template_id = ?
    ORDER BY tq.question_order
");
$stmt->execute([$test_id]);
$questions = $stmt->fetchAll();

// Calculate score
$correct_answers = 0;
$student_answers = [];

foreach ($questions as $question) {
    $question_id = $question['id'];
    $student_answer = isset($_POST["question_$question_id"]) ? $_POST["question_$question_id"] : null;
    
    // Get correct answer
    $stmt = $conn->prepare("
        SELECT option_letter, is_correct 
        FROM question_options 
        WHERE question_id = ? AND is_correct = 1
    ");
    $stmt->execute([$question_id]);
    $correct = $stmt->fetch();
    $correct_answer = $correct ? $correct['option_letter'] : null;
    
    $is_correct = ($student_answer === $correct_answer);
    
    if ($is_correct) {
        $correct_answers++;
    }
    
    $student_answers[] = [
        'question_id' => $question_id,
        'selected_option' => $student_answer,
        'correct_answer' => $correct_answer,
        'is_correct' => $is_correct
    ];
}

$score = $correct_answers;
$total_questions = count($questions);
$passed = ($score >= $test['passing_score']);

// Save test attempt
$stmt = $conn->prepare("
    INSERT INTO student_test_attempts 
    (student_id, test_template_id, score, total_questions, time_taken, passed, started_at, completed_at) 
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([
    $user['id'],
    $test_id,
    $score,
    $total_questions,
    $time_taken,
    $passed ? 1 : 0
]);

$attempt_id = $conn->lastInsertId();

// Save individual answers
foreach ($student_answers as $answer) {
    $stmt = $conn->prepare("
        INSERT INTO student_answers 
        (attempt_id, question_id, selected_option, is_correct) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $attempt_id,
        $answer['question_id'],
        $answer['selected_option'],
        $answer['is_correct'] ? 1 : 0
    ]);
}

// Update question statistics
foreach ($questions as $question) {
    $stmt = $conn->prepare("
        UPDATE questions 
        SET times_answered = times_answered + 1 
        WHERE id = ?
    ");
    $stmt->execute([$question['id']]);
}

// Update correct answer statistics
foreach ($student_answers as $answer) {
    if ($answer['is_correct']) {
        $stmt = $conn->prepare("
            UPDATE questions 
            SET times_correct = times_correct + 1 
            WHERE id = ?
        ");
        $stmt->execute([$answer['question_id']]);
    }
}

// Redirect to results
header('Location: results.php?attempt=' . $attempt_id);
exit;
?>