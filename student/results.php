<?php
require_once '../config.php';
require_once '../includes/language.php';

// Check login
if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user = getCurrentUser();
$attempt_id = isset($_GET['attempt']) ? (int)$_GET['attempt'] : 0;

// Get attempt details
$stmt = $conn->prepare("
    SELECT sta.*, tt.test_code, tt.name_rw, tt.name_en, tt.passing_score, tt.total_questions as test_total
    FROM student_test_attempts sta
    JOIN test_templates tt ON sta.test_template_id = tt.id
    WHERE sta.id = ? AND sta.student_id = ?
");
$stmt->execute([$attempt_id, $user['id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: dashboard.php?error=invalid_attempt');
    exit;
}

// Get all answers with questions
$stmt = $conn->prepare("
    SELECT 
        sa.*,
        q.question_text_rw,
        q.image_path
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.attempt_id = ?
    ORDER BY q.id
");
$stmt->execute([$attempt_id]);
$answers = $stmt->fetchAll();

// Get options for each question
foreach ($answers as &$answer) {
    $stmt = $conn->prepare("
        SELECT * FROM question_options 
        WHERE question_id = ?
        ORDER BY option_letter
    ");
    $stmt->execute([$answer['question_id']]);
    $answer['options'] = $stmt->fetchAll();
}

$percentage = round(($attempt['score'] / $attempt['total_questions']) * 100);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'rw' ? 'Ibisubizo' : 'Results'; ?> - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/results.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="results-header">
        <div class="container">
            <a href="dashboard.php" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <?php echo $current_lang === 'rw' ? 'Subira ku rupapuro rwa mbere' : 'Back to Dashboard'; ?>
            </a>
        </div>
    </header>

    <!-- Results Summary -->
    <section class="results-summary">
        <div class="container">
            <div class="summary-card <?php echo $attempt['passed'] ? 'passed' : 'failed'; ?>">
                <div class="result-icon">
                    <?php if ($attempt['passed']): ?>
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                
                <h1 class="result-title">
                    <?php if ($attempt['passed']): ?>
                        <?php echo $current_lang === 'rw' ? '🎉 Waratsinze!' : '🎉 Congratulations!'; ?>
                    <?php else: ?>
                        <?php echo $current_lang === 'rw' ? 'Ntiwatsinze' : 'Not Passed'; ?>
                    <?php endif; ?>
                </h1>
                
                <div class="score-display">
                    <div class="score-number"><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></div>
                    <div class="score-percentage"><?php echo $percentage; ?>%</div>
                </div>
                
                <div class="score-details">
                    <div class="detail-item">
                        <span class="detail-label"><?php echo $current_lang === 'rw' ? 'Isuzuma:' : 'Test:'; ?></span>
                        <span class="detail-value"><?php echo $attempt['test_code']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><?php echo $current_lang === 'rw' ? 'Igihe cyakoreshejwe:' : 'Time Taken:'; ?></span>
                        <span class="detail-value">
                            <?php 
                            $minutes = floor($attempt['time_taken'] / 60);
                            $seconds = $attempt['time_taken'] % 60;
                            echo sprintf('%d:%02d', $minutes, $seconds);
                            ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><?php echo $current_lang === 'rw' ? 'Amanota yo gutsinza:' : 'Passing Score:'; ?></span>
                        <span class="detail-value"><?php echo $attempt['passing_score']; ?>/<?php echo $attempt['total_questions']; ?></span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="dashboard.php" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Subira ku rupapuro rwa mbere' : 'Back to Dashboard'; ?>
                    </a>
                    <?php if (!$attempt['passed']): ?>
                        <a href="take-test.php?test=<?php echo $attempt['test_template_id']; ?>" class="btn btn-secondary">
                            <?php echo $current_lang === 'rw' ? 'Ongera ugerageze' : 'Try Again'; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Answer Review -->
    <section class="answer-review">
        <div class="container">
            <h2><?php echo $current_lang === 'rw' ? 'Ibisubizo byawe' : 'Your Answers'; ?></h2>
            
            <div class="answers-list">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="answer-card <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                        <div class="answer-header">
                            <div class="question-number">
                                <?php echo $current_lang === 'rw' ? 'Ikibazo' : 'Question'; ?> <?php echo $index + 1; ?>
                            </div>
                            <div class="answer-status">
                                <?php if ($answer['is_correct']): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span><?php echo $current_lang === 'rw' ? 'Ni ukuri' : 'Correct'; ?></span>
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    <span><?php echo $current_lang === 'rw' ? 'Si ukuri' : 'Incorrect'; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($answer['question_text_rw']); ?>
                        </div>
                        
                        <?php if (!empty($answer['image_path'])): ?>
                            <div class="question-images">
                                <?php 
                                $images = explode(',', $answer['image_path']);
                                foreach ($images as $image): 
                                ?>
                                    <img src="../assets/images/questions/<?php echo trim($image); ?>" alt="Traffic Sign" class="question-image">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="options-review">
                            <?php foreach ($answer['options'] as $option): ?>
                                <?php
                                $is_selected = ($option['option_letter'] === $answer['selected_option']);
                                $is_correct_option = $option['is_correct'];
                                $class = '';
                                
                                if ($is_selected && $is_correct_option) {
                                    $class = 'selected correct';
                                } elseif ($is_selected && !$is_correct_option) {
                                    $class = 'selected incorrect';
                                } elseif (!$is_selected && $is_correct_option) {
                                    $class = 'correct-answer';
                                }
                                ?>
                                <div class="option-item <?php echo $class; ?>">
                                    <span class="option-letter"><?php echo $option['option_letter']; ?></span>
                                    <span class="option-text"><?php echo htmlspecialchars($option['option_text_rw']); ?></span>
                                    
                                    <?php if ($is_selected): ?>
                                        <span class="option-badge your-answer">
                                            <?php echo $current_lang === 'rw' ? 'Igisubizo cyawe' : 'Your Answer'; ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_correct_option): ?>
                                        <span class="option-badge correct-badge">
                                            <?php echo $current_lang === 'rw' ? 'Igisubizo nyacyo' : 'Correct Answer'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</body>
</html>