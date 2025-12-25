<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$school_id = $_SESSION['user_id'];
$school_name = $_SESSION['full_name'];
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header('Location: students.php?error=invalid_student');
    exit;
}

// Get student details
try {
    $stmt = $conn->prepare("
        SELECT ss.*, u.full_name, u.email, u.phone, u.created_at as user_created
        FROM school_students ss
        JOIN users u ON ss.student_id = u.id
        WHERE ss.school_id = ? AND ss.student_id = ?
    ");
    $stmt->execute([$school_id, $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Location: students.php?error=student_not_found');
        exit;
    }
    
    // Get student statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_attempts,
            AVG(score) as avg_score,
            MAX(score) as best_score,
            MIN(score) as worst_score
        FROM student_test_attempts
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent test attempts
    $stmt = $conn->prepare("
        SELECT sta.*, t.test_code, t.name_en
        FROM student_test_attempts sta
        JOIN test_templates t ON sta.test_template_id = t.id
        WHERE sta.student_id = ?
        ORDER BY sta.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$student_id]);
    $recent_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pass rate
    $pass_rate = $stats['total_attempts'] > 0 ? 
        round(($stats['passed_attempts'] / $stats['total_attempts']) * 100, 1) : 0;
    
} catch (Exception $e) {
    error_log("View student error: " . $e->getMessage());
    header('Location: students.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as before) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🏫 URUHUSHYA</h2>
            <p>School Portal</p>
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
            
            <a href="students.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Students
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
            <h1>Student Details</h1>
            <div class="admin-info">
                <a href="students.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Students
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="user-profile-card">
                <!-- Student Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php 
                        $initials = strtoupper(substr($student['full_name'], 0, 1));
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                        <p class="user-type">
                            Student Code: <strong><?php echo htmlspecialchars($student['student_code']); ?></strong>
                        </p>
                        <p style="color: #6B7280; margin-top: 4px;">
                            Joined: <?php echo date('M d, Y', strtotime($student['user_created'])); ?>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <a href="student-edit.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit Student
                        </a>
                        
                        <span class="status-badge <?php echo $student['status']; ?>" style="margin-left: 12px; font-size: 14px;">
                            <?php echo ucfirst($student['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($student['phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($student['email'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Student Code</label>
                        <p><strong><?php echo htmlspecialchars($student['student_code']); ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Status</label>
                        <p>
                            <span class="status-badge <?php echo $student['status']; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Notes -->
                <?php if (!empty($student['notes'])): ?>
                    <div style="margin-top: 24px; padding: 16px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 8px;">
                        <h4 style="margin-bottom: 8px; color: #92400E;">Notes</h4>
                        <p style="color: #92400E;"><?php echo nl2br(htmlspecialchars($student['notes'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Performance Statistics -->
                <div class="user-stats" style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px;">Performance Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h4><?php echo $stats['total_attempts']; ?></h4>
                            <p>Total Tests</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo $stats['passed_attempts']; ?></h4>
                            <p>Tests Passed</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo $pass_rate; ?>%</h4>
                            <p>Pass Rate</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) : 0; ?></h4>
                            <p>Average Score</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo $stats['best_score'] ?? 0; ?></h4>
                            <p>Best Score</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Test Attempts -->
                <div style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px;">Recent Test Attempts</h3>
                    <?php if (empty($recent_tests)): ?>
                        <div class="empty-state">
                            <p>No test attempts yet</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Test Code</th>
                                    <th>Test Name</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tests as $test): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['test_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($test['name_en']); ?></td>
                                        <td><?php echo $test['score']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $test['passed'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $test['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($test['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>