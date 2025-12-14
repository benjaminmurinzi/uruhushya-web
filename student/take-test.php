<?php
require_once '../config.php';
require_once '../includes/language.php';

// Check login
if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user = getCurrentUser();
$test_id = isset($_GET['test']) ? (int)$_GET['test'] : 0;

// Get test details
$stmt = $conn->prepare("SELECT * FROM test_templates WHERE id = ?");
$stmt->execute([$test_id]);
$test = $stmt->fetch();

if (!$test) {
    header('Location: dashboard.php?error=test_not_found');
    exit;
}

// Check if test is free or user has subscription
if (!$test['is_free']) {
    // TODO: Check subscription - for now, block access
    $subscription_days = 0; // Replace with actual subscription check
    if ($subscription_days <= 0) {
        header('Location: dashboard.php?error=subscription_required');
        exit;
    }
}

// Get random 20 questions for this test
$stmt = $conn->prepare("
    SELECT q.*, tq.question_order 
    FROM test_questions tq
    JOIN questions q ON tq.question_id = q.id
    WHERE tq.test_template_id = ?
    ORDER BY tq.question_order
");
$stmt->execute([$test_id]);
$questions = $stmt->fetchAll();

// Get options for each question
foreach ($questions as &$question) {
    $stmt = $conn->prepare("
        SELECT * FROM question_options 
        WHERE question_id = ? 
        ORDER BY option_letter
    ");
    $stmt->execute([$question['id']]);
    $question['options'] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $test['test_code']; ?> - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/take-test.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Test Header -->
    <header class="test-header">
        <div class="container">
            <div class="test-info">
                <h1><?php echo $test['test_code']; ?></h1>
                <span class="test-subtitle"><?php echo $current_lang === 'rw' ? $test['name_rw'] : $test['name_en']; ?></span>
            </div>
            
            <div class="timer-section">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span id="timer"><?php echo $test['time_limit_minutes']; ?>:00</span>
            </div>
        </div>
    </header>

    <!-- Progress Bar -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progressBar"></div>
        </div>
        <div class="progress-text">
            <span id="currentQuestion">1</span> / <span id="totalQuestions"><?php echo count($questions); ?></span>
        </div>
    </div>

    <!-- Test Form -->
    <main class="test-main">
        <div class="container">
            <form id="testForm" method="POST" action="submit-test.php">
                <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                <input type="hidden" name="start_time" id="startTime" value="<?php echo time(); ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card" data-question="<?php echo $index + 1; ?>" style="<?php echo $index === 0 ? '' : 'display:none;'; ?>">
                        <div class="question-number">
                            <?php echo $current_lang === 'rw' ? 'Ikibazo' : 'Question'; ?> <?php echo $index + 1; ?>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($question['question_text_rw']); ?>
                        </div>
                        
                        <?php if (!empty($question['image_path'])): ?>
                            <div class="question-images">
                                <?php 
                                $images = explode(',', $question['image_path']);
                                foreach ($images as $image): 
                                ?>
                                    <img src="../assets/images/questions/<?php echo trim($image); ?>" alt="Traffic Sign" class="question-image">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="options-container">
                            <?php foreach ($question['options'] as $option): ?>
                                <label class="option-label">
                                    <input 
                                        type="radio" 
                                        name="question_<?php echo $question['id']; ?>" 
                                        value="<?php echo $option['option_letter']; ?>"
                                        required
                                    >
                                    <div class="option-content">
                                        <span class="option-letter"><?php echo $option['option_letter']; ?></span>
                                        <span class="option-text"><?php echo htmlspecialchars($option['option_text_rw']); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="question-navigation">
                            <?php if ($index > 0): ?>
                                <button type="button" class="btn btn-secondary" onclick="previousQuestion()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                    <?php echo $current_lang === 'rw' ? 'Inyuma' : 'Previous'; ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($index < count($questions) - 1): ?>
                                <button type="button" class="btn btn-primary" onclick="nextQuestion()">
                                    <?php echo $current_lang === 'rw' ? 'Komeza' : 'Next'; ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success" onclick="confirmSubmit()">
                                    <?php echo $current_lang === 'rw' ? 'Ohereza Ibisubizo' : 'Submit Answers'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        </div>
    </main>

    <!-- Submit Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2><?php echo $current_lang === 'rw' ? 'Emeza Kohereza' : 'Confirm Submission'; ?></h2>
            <p id="answeredCount"></p>
            <p><?php echo $current_lang === 'rw' ? 'Uremeza ko ushaka kohereza ibisubizo byawe?' : 'Are you sure you want to submit your answers?'; ?></p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeConfirmModal()">
                    <?php echo $current_lang === 'rw' ? 'Gusuzuma' : 'Review'; ?>
                </button>
                <button class="btn btn-success" onclick="submitTest()">
                    <?php echo $current_lang === 'rw' ? 'Yego, Ohereza' : 'Yes, Submit'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Time's Up Modal -->
    <div id="timeUpModal" class="modal">
        <div class="modal-content">
            <h2>⏰ <?php echo $current_lang === 'rw' ? 'Igihe Cyarangiye!' : 'Time\'s Up!'; ?></h2>
            <p><?php echo $current_lang === 'rw' ? 'Ibisubizo byawe byoherejwe ku buryo bwikora.' : 'Your answers have been submitted automatically.'; ?></p>
        </div>
    </div>

    <script>
        // Timer variables
        const timeLimit = <?php echo $test['time_limit_minutes'] * 60; ?>; // in seconds
        let timeRemaining = timeLimit;
        let timerInterval;
        
        // Question tracking
        let currentQuestionIndex = 1;
        const totalQuestions = <?php echo count($questions); ?>;
        
        // Start timer
        function startTimer() {
            timerInterval = setInterval(() => {
                timeRemaining--;
                updateTimerDisplay();
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    autoSubmitTest();
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Warning when 5 minutes left
            if (timeRemaining === 300) {
                alert('<?php echo $current_lang === 'rw' ? 'Hasigaye iminota 5!' : '5 minutes remaining!'; ?>');
            }
            
            // Change color when 1 minute left
            if (timeRemaining <= 60) {
                document.getElementById('timer').style.color = '#EF4444';
            }
        }
        
        // Question navigation
        function nextQuestion() {
            // Check if current question is answered
            const currentCard = document.querySelector(`.question-card[data-question="${currentQuestionIndex}"]`);
            const radios = currentCard.querySelectorAll('input[type="radio"]');
            const answered = Array.from(radios).some(radio => radio.checked);
            
            if (!answered) {
                alert('<?php echo $current_lang === 'rw' ? 'Hitamo igisubizo mbere yo gukomeza.' : 'Please select an answer before continuing.'; ?>');
                return;
            }
            
            if (currentQuestionIndex < totalQuestions) {
                // Hide current question
                currentCard.style.display = 'none';
                
                // Show next question
                currentQuestionIndex++;
                document.querySelector(`.question-card[data-question="${currentQuestionIndex}"]`).style.display = 'block';
                
                // Update progress
                updateProgress();
                
                // Scroll to top
                window.scrollTo(0, 0);
            }
        }
        
        function previousQuestion() {
            if (currentQuestionIndex > 1) {
                // Hide current question
                document.querySelector(`.question-card[data-question="${currentQuestionIndex}"]`).style.display = 'none';
                
                // Show previous question
                currentQuestionIndex--;
                document.querySelector(`.question-card[data-question="${currentQuestionIndex}"]`).style.display = 'block';
                
                // Update progress
                updateProgress();
                
                // Scroll to top
                window.scrollTo(0, 0);
            }
        }
        
        function updateProgress() {
            document.getElementById('currentQuestion').textContent = currentQuestionIndex;
            const progress = (currentQuestionIndex / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        // Submit confirmation
        function confirmSubmit() {
            const form = document.getElementById('testForm');
            const formData = new FormData(form);
            let answeredCount = 0;
            
            // Count answered questions
            for (let i = 0; i < totalQuestions; i++) {
                const questionInputs = form.querySelectorAll(`input[name^="question_"]`);
                const uniqueQuestions = new Set();
                questionInputs.forEach(input => {
                    if (input.checked) {
                        uniqueQuestions.add(input.name);
                    }
                });
                answeredCount = uniqueQuestions.size;
            }
            
            const unanswered = totalQuestions - answeredCount;
            
            document.getElementById('answeredCount').textContent = 
                `<?php echo $current_lang === 'rw' ? 'Wasubije ibibazo' : 'You answered'; ?> ${answeredCount}/${totalQuestions}. ` +
                (unanswered > 0 ? `(${unanswered} <?php echo $current_lang === 'rw' ? 'ntasubije' : 'unanswered'; ?>)` : '');
            
            document.getElementById('confirmModal').style.display = 'flex';
        }
        
        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        function submitTest() {
            clearInterval(timerInterval);
            
            // Add time taken
            const timeTaken = timeLimit - timeRemaining;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'time_taken';
            input.value = timeTaken;
            document.getElementById('testForm').appendChild(input);
            
            document.getElementById('testForm').submit();
        }
        
        function autoSubmitTest() {
            document.getElementById('timeUpModal').style.display = 'flex';
            setTimeout(() => {
                submitTest();
            }, 2000);
        }
        
        // Prevent accidental page close
        window.addEventListener('beforeunload', function (e) {
            if (timeRemaining > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Start timer on page load
        window.addEventListener('load', function() {
            startTimer();
        });
    </script>
</body>
</html>