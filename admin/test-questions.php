<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($test_id <= 0) {
    header('Location: tests.php?error=invalid_test');
    exit;
}

// Get test details
try {
    $stmt = $conn->prepare("SELECT * FROM test_templates WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        header('Location: tests.php?error=test_not_found');
        exit;
    }
    
    // Get assigned questions
    $stmt = $conn->prepare("
        SELECT q.*, tq.question_order 
        FROM questions q
        JOIN test_questions tq ON q.id = tq.question_id
        WHERE tq.test_template_id = ?
        ORDER BY tq.question_order ASC
    ");
    $stmt->execute([$test_id]);
    $assigned_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available questions (not assigned to this test)
    $stmt = $conn->prepare("
        SELECT q.* 
        FROM questions q
        WHERE q.id NOT IN (
            SELECT question_id FROM test_questions WHERE test_template_id = ?
        )
        ORDER BY q.id ASC
        LIMIT 100
    ");
    $stmt->execute([$test_id]);
    $available_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Test questions error: " . $e->getMessage());
    header('Location: tests.php?error=system_error');
    exit;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <style>
        .questions-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .questions-panel {
            background: white;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
        }
        
        .questions-panel h3 {
            margin-bottom: 16px;
            color: #1F2937;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .question-item {
            padding: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #F9FAFB;
        }
        
        .question-text {
            flex: 1;
            font-size: 14px;
            color: #1F2937;
        }
        
        .question-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-add, .btn-remove {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-add {
            background: #10B981;
            color: white;
        }
        
        .btn-add:hover {
            background: #059669;
        }
        
        .btn-remove {
            background: #EF4444;
            color: white;
        }
        
        .btn-remove:hover {
            background: #DC2626;
        }
        
        .empty-questions {
            text-align: center;
            padding: 40px 20px;
            color: #6B7280;
        }
        
        @media (max-width: 992px) {
            .questions-container {
                grid-template-columns: 1fr;
            }
        }
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
       }

       .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981; 
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
     }
     
    </style>
</head>
<body>
    <!-- Sidebar -->
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
            
            <a href="tests.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Tests
            </a>
            
            <a href="questions.php" class="nav-item">
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
            <h1>Manage Questions: <?php echo htmlspecialchars($test['test_code']); ?></h1>
            <div class="admin-info">
                <a href="tests.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Tests
                </a>
            </div>
        </header>

        <section class="content-section">
            <?php if ($success === 'added'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Question added successfully!
                </div>
            <?php elseif ($success === 'removed'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Question removed successfully!
                </div>
            <?php endif; ?>

            <div class="questions-container">
                <!-- Assigned Questions -->
                <div class="questions-panel">
                    <h3>
                        <span>Assigned Questions (<?php echo count($assigned_questions); ?>/<?php echo $test['total_questions']; ?>)</span>
                        <div style="display: flex; gap: 8px;">
                           <?php if (count($assigned_questions) < $test['total_questions']): ?>
                              <a href="test-auto-assign.php?id=<?php echo $test_id; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                 <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                     <polyline points="23 4 23 10 17 10"></polyline>
                                     <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                 </svg>
                                 Auto-Fill
                               </a>
                           <?php endif; ?>
        
                           <?php if (count($assigned_questions) >= $test['total_questions']): ?>
                                <span class="status-badge active">Complete</span>
                           <?php else: ?>
                                <span class="status-badge pending">Incomplete</span>
                           <?php endif; ?>
                       </div>
                    </h3>
                    <h3>
                        <span>Assigned Questions (<?php echo count($assigned_questions); ?>/<?php echo $test['total_questions']; ?>)</span>
                        <?php if (count($assigned_questions) >= $test['total_questions']): ?>
                            <span class="status-badge active">Complete</span>
                        <?php else: ?>
                            <span class="status-badge pending">Incomplete</span>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if (empty($assigned_questions)): ?>
                        <div class="empty-questions">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <p>No questions assigned yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($assigned_questions as $question): ?>
                            <div class="question-item">
                                <div class="question-text">
                                    <strong>Q<?php echo $question['question_order']; ?>:</strong>
                                    <?php echo htmlspecialchars(substr($question['question_text_rw'], 0, 100)); ?>...
                                </div>
                                <div class="question-actions">
                                    <form method="POST" action="test-question-remove.php" style="margin: 0;">
                                        <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                        <button type="submit" class="btn-remove">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Available Questions -->
                <div class="questions-panel">
                    <h3>Available Questions (<?php echo count($available_questions); ?>)</h3>
                    
                    <?php if (empty($available_questions)): ?>
                        <div class="empty-questions">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p>No available questions</p>
                            <a href="question-add.php" class="btn btn-primary" style="margin-top: 16px;">
                                Add New Question
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($available_questions as $question): ?>
                                <div class="question-item">
                                    <div class="question-text">
                                        <strong>#<?php echo $question['id']; ?>:</strong>
                                        <?php echo htmlspecialchars(substr($question['question_text_rw'], 0, 80)); ?>...
                                    </div>
                                    <div class="question-actions">
                                        <form method="POST" action="test-question-add.php" style="margin: 0;">
                                            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                            <button type="submit" class="btn-add">Add</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>