<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($question_id <= 0) {
    header('Location: questions.php?error=invalid_question');
    exit;
}

// Get question details
try {
    $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$question) {
        header('Location: questions.php?error=question_not_found');
        exit;
    }
    
    // Get tests using this question
    $stmt = $conn->prepare("
        SELECT tt.id, tt.test_code, tt.name_en, tq.question_order
        FROM test_templates tt
        JOIN test_questions tq ON tt.id = tq.test_template_id
        WHERE tq.question_id = ?
        ORDER BY tt.test_code ASC
    ");
    $stmt->execute([$question_id]);
    $tests_using = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Question view error: " . $e->getMessage());
    header('Location: questions.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Question - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <style>
        .question-preview {
            background: white;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .question-image {
            max-width: 100%;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #E5E7EB;
        }
        
        .options-list {
            margin: 24px 0;
        }
        
        .option-item {
            padding: 16px;
            margin: 12px 0;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .option-item.correct {
            background: #D1FAE5;
            border-color: #10B981;
        }
        
        .option-letter {
            width: 40px;
            height: 40px;
            background: #3B82F6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .option-item.correct .option-letter {
            background: #10B981;
        }
        
        .explanation-box {
            background: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar (same structure as before) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>URUHUSHYA</h2>
            <p>Admin Panel</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
            
            <a href="users.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Users
            </a>
            
            <a href="tests.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Tests
            </a>
            
            <a href="questions.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Questions
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <h1>Question #<?php echo $question_id; ?></h1>
            <div class="admin-info">
                <a href="questions.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Questions
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="user-profile-card">
                <!-- Question Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <h2>Question #<?php echo $question_id; ?></h2>
                        <?php if (!empty($question['category'])): ?>
                            <p class="user-type"><?php echo htmlspecialchars($question['category']); ?></p>
                        <?php endif; ?>
                        <p style="color: #6B7280; margin-top: 4px;">Correct Answer: <strong><?php echo strtoupper($question['correct_answer']); ?></strong></p>
                    </div>
                    <div class="profile-actions">
                        <a href="question-edit.php?id=<?php echo $question['id']; ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit Question
                        </a>
                    </div>
                </div>

                <!-- Question Preview - Kinyarwanda -->
                <div class="question-preview">
                    <h3 style="margin-bottom: 16px; color: #1F2937;">Question (Kinyarwanda)</h3>
                    <p style="font-size: 18px; line-height: 1.6; color: #1F2937; margin-bottom: 20px;">
                        <?php echo nl2br(htmlspecialchars($question['question_text_rw'])); ?>
                    </p>

                    <?php if (!empty($question['question_image'])): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($question['question_image']); ?>" 
                             alt="Question Image" 
                             class="question-image">
                    <?php endif; ?>

                    <div class="options-list">
                        <div class="option-item <?php echo $question['correct_answer'] === 'A' ? 'correct' : ''; ?>">
                            <div class="option-letter">A</div>
                            <div><?php echo htmlspecialchars($question['option_a_rw']); ?></div>
                        </div>
                        <div class="option-item <?php echo $question['correct_answer'] === 'B' ? 'correct' : ''; ?>">
                            <div class="option-letter">B</div>
                            <div><?php echo htmlspecialchars($question['option_b_rw']); ?></div>
                        </div>
                        <div class="option-item <?php echo $question['correct_answer'] === 'C' ? 'correct' : ''; ?>">
                            <div class="option-letter">C</div>
                            <div><?php echo htmlspecialchars($question['option_c_rw']); ?></div>
                        </div>
                        <div class="option-item <?php echo $question['correct_answer'] === 'D' ? 'correct' : ''; ?>">
                            <div class="option-letter">D</div>
                            <div><?php echo htmlspecialchars($question['option_d_rw']); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($question['explanation_rw'])): ?>
                        <div class="explanation-box">
                            <strong>Explanation:</strong><br>
                            <?php echo nl2br(htmlspecialchars($question['explanation_rw'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Question Preview - English -->
                <div class="question-preview">
                    <h3 style="margin-bottom: 16px; color: #1F2937;">Question (English)</h3>
                    <p style="font-size: 18px; line-height: 1.6; color: #1F2937; margin-bottom: 20px;">
                        <?php echo nl2br(htmlspecialchars($question['question_text_en'])); ?>
                    </p>

                    <div class="options-list">
                        <div class="option-item <?php echo $question['correct_answer'] === 'A' ? 'correct' : ''; ?>">
                            <div class="option-letter">A</div>
                            <div><?php echo htmlspecialchars($question['option_a_en']); ?></div>
                        </div>
                        <div class="option-item <?php echo $question['correct_answer'] === 'B' ? 'correct' : ''; ?>">
                            <div class="option-letter">B</div>
                            <div><?php echo htmlspecialchars($question['option_b_en']); ?></div>
                        </div>
                        <div class="option-item <?php echo $question['correct_answer'] === 'C' ? 'correct' : ''; ?>">
                            <div class="option-letter">C</div>
                            <div><?php echo htmlspecialchars($question['option_c_en']); ?></div>
                        </div>
                        <div class="option-item <?php echo $question['correct_answer'] === 'D' ? 'correct' : ''; ?>">
                            <div class="option-letter">D</div>
                            <div><?php echo htmlspecialchars($question['option_d_en']); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($question['explanation_en'])): ?>
                        <div class="explanation-box">
                            <strong>Explanation:</strong><br>
                            <?php echo nl2br(htmlspecialchars($question['explanation_en'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tests Using This Question -->
                <?php if (!empty($tests_using)): ?>
                    <div class="user-stats" style="margin-top: 32px;">
                        <h3>Used in <?php echo count($tests_using); ?> Test(s)</h3>
                        <table style="margin-top: 16px;">
                            <thead>
                                <tr>
                                    <th>Test Code</th>
                                    <th>Test Name</th>
                                    <th>Question Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tests_using as $test): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['test_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($test['name_en']); ?></td>
                                        <td>Question #<?php echo $test['question_order']; ?></td>
                                        <td>
                                            <a href="test-view.php?id=<?php echo $test['id']; ?>" class="btn-icon">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 40px;">
                        <p>This question is not currently used in any tests</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>