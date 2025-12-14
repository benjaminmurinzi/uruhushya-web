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
    
    // Get question count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_questions WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $question_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get attempt statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $total_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE test_template_id = ? AND passed = 1");
    $stmt->execute([$test_id]);
    $passed_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM student_test_attempts WHERE test_template_id = ?");
    $stmt->execute([$test_id]);
    $avg_score = $stmt->fetch(PDO::FETCH_ASSOC)['avg_score'];
    
    // Get recent attempts
    $stmt = $conn->prepare("
        SELECT sta.*, u.full_name 
        FROM student_test_attempts sta
        JOIN users u ON sta.student_id = u.id
        WHERE sta.test_template_id = ?
        ORDER BY sta.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$test_id]);
    $recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Test view error: " . $e->getMessage());
    header('Location: tests.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Test - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
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
            <h1>Test Details</h1>
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
            <div class="user-profile-card">
                <!-- Test Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($test['test_code']); ?></h2>
                        <p class="user-type"><?php echo htmlspecialchars($test['name_en']); ?></p>
                        <p style="color: #6B7280; margin-top: 4px;"><?php echo htmlspecialchars($test['name_rw']); ?></p>
                    </div>
                    <div class="profile-actions">
                        <a href="test-edit.php?id=<?php echo $test['id']; ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit Test
                        </a>
                        
                        <a href="test-questions.php?id=<?php echo $test['id']; ?>" class="btn btn-success">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            Manage Questions
                        </a>
                    </div>
                </div>

                <!-- Test Details -->
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Test Code</label>
                        <p><?php echo htmlspecialchars($test['test_code']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Total Questions</label>
                        <p><?php echo $test['total_questions']; ?> questions</p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Time Limit</label>
                        <p><?php echo $test['time_limit_minutes']; ?> minutes</p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Passing Score</label>
                        <p><?php echo $test['passing_score']; ?>%</p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Questions Assigned</label>
                        <p>
                            <span class="<?php echo $question_count >= $test['total_questions'] ? 'status-badge active' : 'status-badge pending'; ?>">
                                <?php echo $question_count; ?> / <?php echo $test['total_questions']; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Descriptions -->
                <?php if (!empty($test['description_rw']) || !empty($test['description_en'])): ?>
                    <div style="margin: 32px 0;">
                        <h3 style="margin-bottom: 16px;">Descriptions</h3>
                        <div class="form-row">
                            <?php if (!empty($test['description_rw'])): ?>
                                <div>
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #6B7280;">Kinyarwanda</label>
                                    <p style="color: #1F2937;"><?php echo nl2br(htmlspecialchars($test['description_rw'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($test['description_en'])): ?>
                                <div>
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #6B7280;">English</label>
                                    <p style="color: #1F2937;"><?php echo nl2br(htmlspecialchars($test['description_en'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="user-stats">
                    <h3>Test Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h4><?php echo number_format($total_attempts); ?></h4>
                            <p>Total Attempts</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php echo number_format($passed_attempts); ?></h4>
                            <p>Passed</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php echo $total_attempts > 0 ? round(($passed_attempts / $total_attempts) * 100) : 0; ?>%</h4>
                            <p>Pass Rate</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php echo $avg_score ? round($avg_score, 1) : 0; ?></h4>
                            <p>Average Score</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Attempts -->
                <?php if (!empty($recent_attempts)): ?>
                    <div class="recent-attempts">
                        <h3>Recent Test Attempts</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Result</th>
                                    <th>Date</th>
                                    <th>Time Taken</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attempts as $attempt): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($attempt['full_name']); ?></strong></td>
                                        <td><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></td>
                                        <td><?php echo round(($attempt['score'] / $attempt['total_questions']) * 100); ?>%</td>
                                        <td>
                                            <span class="status-badge <?php echo $attempt['passed'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $attempt['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($attempt['created_at'])); ?></td>
                                        <td><?php echo gmdate('i:s', $attempt['time_taken']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>