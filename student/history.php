<?php
require_once '../config.php';
require_once '../includes/language.php';

if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user = getCurrentUser();

// Get all test attempts
$stmt = $conn->prepare("
    SELECT 
        sta.*,
        tt.test_code,
        tt.name_rw,
        tt.name_en
    FROM student_test_attempts sta
    JOIN test_templates tt ON sta.test_template_id = tt.id
    WHERE sta.student_id = ?
    ORDER BY sta.completed_at DESC
");
$stmt->execute([$user['id']]);
$attempts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'rw' ? 'Amateka' : 'History'; ?> - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/history.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="history-header">
        <div class="container">
            <a href="dashboard.php" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <?php echo $current_lang === 'rw' ? 'Subira' : 'Back'; ?>
            </a>
            <h1><?php echo $current_lang === 'rw' ? 'Amateka y\'Amasuzuma' : 'Test History'; ?></h1>
        </div>
    </header>

    <main class="history-main">
        <div class="container">
            
            <?php if (empty($attempts)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <h3><?php echo $current_lang === 'rw' ? 'Nta masuzuma wakoze' : 'No test history yet'; ?></h3>
                    <p><?php echo $current_lang === 'rw' ? 'Tangira isuzuma kugirango ubone amateka yawe hano' : 'Take a test to see your history here'; ?></p>
                    <a href="dashboard.php" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Reba Amasuzuma' : 'View Tests'; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($attempts as $attempt): ?>
                        <?php
                        $percentage = round(($attempt['score'] / $attempt['total_questions']) * 100);
                        $minutes = floor($attempt['time_taken'] / 60);
                        $seconds = $attempt['time_taken'] % 60;
                        ?>
                        <div class="history-card <?php echo $attempt['passed'] ? 'passed' : 'failed'; ?>">
                            <div class="history-header">
                                <div class="test-info">
                                    <h3><?php echo $attempt['test_code']; ?></h3>
                                    <p class="test-date">
                                        <?php echo date('M d, Y - H:i', strtotime($attempt['completed_at'])); ?>
                                    </p>
                                </div>
                                <div class="test-status">
                                    <?php if ($attempt['passed']): ?>
                                        <span class="badge badge-success">
                                            ✅ <?php echo $current_lang === 'rw' ? 'Yatsinze' : 'Passed'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-fail">
                                            ❌ <?php echo $current_lang === 'rw' ? 'Ntiyatsinze' : 'Failed'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="history-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $current_lang === 'rw' ? 'Amanota:' : 'Score:'; ?></span>
                                    <span class="detail-value score"><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $current_lang === 'rw' ? 'Ijanisha:' : 'Percentage:'; ?></span>
                                    <span class="detail-value"><?php echo $percentage; ?>%</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $current_lang === 'rw' ? 'Igihe:' : 'Time:'; ?></span>
                                    <span class="detail-value"><?php echo sprintf('%d:%02d', $minutes, $seconds); ?></span>
                                </div>
                            </div>
                            
                            <div class="history-actions">
                                <a href="results.php?attempt=<?php echo $attempt['id']; ?>" class="btn btn-view">
                                    <?php echo $current_lang === 'rw' ? 'Reba Ibisubizo' : 'View Results'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>