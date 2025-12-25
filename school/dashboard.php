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

// Get school statistics
try {
    // Total students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM school_students WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$school_id]);
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Tests completed this month
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM student_test_attempts sta
        INNER JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ? 
        AND MONTH(sta.created_at) = MONTH(CURRENT_DATE())
        AND YEAR(sta.created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$school_id]);
    $tests_this_month = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Average pass rate
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_attempts
        FROM student_test_attempts sta
        INNER JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ?
    ");
    $stmt->execute([$school_id]);
    $pass_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $pass_rate = $pass_data['total_attempts'] > 0 ? 
        round(($pass_data['passed_attempts'] / $pass_data['total_attempts']) * 100, 1) : 0;
    
    // Pending test assignments
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM test_assignments 
        WHERE school_id = ? AND status = 'pending'
    ");
    $stmt->execute([$school_id]);
    $pending_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get school subscription
    $stmt = $conn->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$school_id]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent student activity
    $stmt = $conn->prepare("
        SELECT u.full_name, t.test_code, sta.score, sta.passed, sta.created_at
        FROM student_test_attempts sta
        INNER JOIN school_students ss ON sta.student_id = ss.student_id
        INNER JOIN users u ON sta.student_id = u.id
        INNER JOIN test_templates t ON sta.test_template_id = t.id
        WHERE ss.school_id = ?
        ORDER BY sta.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$school_id]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top performing students
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            COUNT(sta.id) as total_tests,
            SUM(CASE WHEN sta.passed = 1 THEN 1 ELSE 0 END) as passed_tests,
            AVG(sta.score) as avg_score
        FROM school_students ss
        INNER JOIN users u ON ss.student_id = u.id
        LEFT JOIN student_test_attempts sta ON ss.student_id = sta.student_id
        WHERE ss.school_id = ?
        GROUP BY ss.student_id
        HAVING total_tests > 0
        ORDER BY avg_score DESC
        LIMIT 5
    ");
    $stmt->execute([$school_id]);
    $top_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("School dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Dashboard - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🏫 URUHUSHYA</h2>
            <p>School Portal</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
            
            <a href="students.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Students
            </a>
            
            <a href="test-assignment.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Test Assignment
                <?php if ($pending_assignments > 0): ?>
                    <span class="badge"><?php echo $pending_assignments; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="reports.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Reports
            </a>
            
            <a href="subscription.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Subscription
            </a>
            
            <a href="bulk-operations.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Bulk Operations
            </a>
            
            <a href="profile.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Profile
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
        <!-- Top Bar -->
        <header class="top-bar">
            <h1>Dashboard</h1>
            <div class="admin-info">
                <span>🏫 <strong><?php echo htmlspecialchars($school_name); ?></strong></span>
            </div>
        </header>

        <!-- Content Section -->
        <section class="content-section">
            
            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card school-theme">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($tests_this_month); ?></h3>
                        <p>Tests This Month</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pass_rate; ?>%</h3>
                        <p>Pass Rate</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($pending_assignments); ?></h3>
                        <p>Pending Assignments</p>
                    </div>
                </div>
            </div>

            <!-- Subscription Status -->
            <?php if ($subscription): ?>
                <div class="alert alert-info" style="margin-bottom: 32px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div>
                        <strong>Subscription Status:</strong> 
                        <span class="status-badge <?php echo $subscription['status']; ?>">
                            <?php echo ucfirst($subscription['status']); ?>
                        </span>
                        | Plan: <?php echo htmlspecialchars($subscription['plan_name']); ?>
                        | Expires: <?php echo date('M d, Y', strtotime($subscription['end_date'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions-grid" style="margin-bottom: 40px;">
                <a href="students.php" class="action-card">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <h3>Add Student</h3>
                    <p>Register new students</p>
                </a>

                <a href="test-assignment.php" class="action-card">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <h3>Assign Tests</h3>
                    <p>Assign tests to students</p>
                </a>

                <a href="reports.php" class="action-card">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <h3>View Reports</h3>
                    <p>Performance analytics</p>
                </a>

                <a href="bulk-operations.php" class="action-card">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <h3>Bulk Upload</h3>
                    <p>Import multiple students</p>
                </a>
            </div>

            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                
                <!-- Recent Activity -->
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">Recent Student Activity</h2>
                    <?php if (empty($recent_activity)): ?>
                        <div class="empty-state">
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Test</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($activity['test_code']); ?></strong></td>
                                        <td><?php echo $activity['score']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $activity['passed'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $activity['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Top Students -->
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">Top Performing Students</h2>
                    <?php if (empty($top_students)): ?>
                        <div class="empty-state">
                            <p>No student data yet</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Tests</th>
                                    <th>Passed</th>
                                    <th>Avg Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td><?php echo $student['total_tests']; ?></td>
                                        <td><?php echo $student['passed_tests']; ?></td>
                                        <td><strong><?php echo round($student['avg_score'], 1); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <style>
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .action-card:hover {
            border-color: #10B981;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
            transform: translateY(-2px);
        }
        
        .action-card svg {
            color: #10B981;
            margin-bottom: 12px;
        }
        
        .action-card h3 {
            margin: 0 0 8px 0;
            color: #1F2937;
        }
        
        .action-card p {
            margin: 0;
            color: #6B7280;
            font-size: 14px;
        }
    </style>
</body>
</html>