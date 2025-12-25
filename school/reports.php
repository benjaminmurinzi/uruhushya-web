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

// Get date range filter
$date_filter = $_GET['period'] ?? '30';
$start_date = date('Y-m-d', strtotime("-{$date_filter} days"));
$end_date = date('Y-m-d');

// Get overall statistics
try {
    // Total students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM school_students WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$school_id]);
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total test attempts in period
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM student_test_attempts sta
        JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ? 
        AND DATE(sta.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    $total_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pass rate
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN sta.passed = 1 THEN 1 ELSE 0 END) as passed
        FROM student_test_attempts sta
        JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ?
        AND DATE(sta.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    $pass_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $pass_rate = $pass_data['total'] > 0 ? round(($pass_data['passed'] / $pass_data['total']) * 100, 1) : 0;
    
    // Average score
    $stmt = $conn->prepare("
        SELECT AVG(sta.score) as avg_score
        FROM student_test_attempts sta
        JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ?
        AND DATE(sta.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    $avg_score = $stmt->fetch(PDO::FETCH_ASSOC)['avg_score'];
    $avg_score = $avg_score ? round($avg_score, 1) : 0;
    
    // Top performing students
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            COUNT(sta.id) as total_tests,
            SUM(CASE WHEN sta.passed = 1 THEN 1 ELSE 0 END) as passed_tests,
            AVG(sta.score) as avg_score
        FROM school_students ss
        JOIN users u ON ss.student_id = u.id
        LEFT JOIN student_test_attempts sta ON ss.student_id = sta.student_id
        WHERE ss.school_id = ?
        AND DATE(sta.created_at) BETWEEN ? AND ?
        GROUP BY ss.student_id
        HAVING total_tests > 0
        ORDER BY avg_score DESC
        LIMIT 10
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    $top_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Students who need help (failed multiple times)
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            COUNT(sta.id) as total_tests,
            SUM(CASE WHEN sta.passed = 0 THEN 1 ELSE 0 END) as failed_tests,
            AVG(sta.score) as avg_score
        FROM school_students ss
        JOIN users u ON ss.student_id = u.id
        LEFT JOIN student_test_attempts sta ON ss.student_id = sta.student_id
        WHERE ss.school_id = ?
        AND DATE(sta.created_at) BETWEEN ? AND ?
        GROUP BY ss.student_id
        HAVING failed_tests >= 2
        ORDER BY failed_tests DESC, avg_score ASC
        LIMIT 10
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    $struggling_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test performance breakdown
    $stmt = $conn->prepare("
        SELECT 
            t.test_code,
            t.name_en,
            COUNT(sta.id) as attempts,
            SUM(CASE WHEN sta.passed = 1 THEN 1 ELSE 0 END) as passed,
            AVG(sta.score) as avg_score
        FROM test_templates t
        LEFT JOIN student_test_attempts sta ON t.id = sta.test_template_id
        LEFT JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ?
        AND DATE(sta.created_at) BETWEEN ? AND ?
        GROUP BY t.id
        HAVING attempts > 0
        ORDER BY attempts DESC
        LIMIT 10
    ");
    $stmt->execute([$school_id, $start_date, $end_date]);
    $test_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily test activity (last 7 days)
    $stmt = $conn->prepare("
        SELECT 
            DATE(sta.created_at) as date,
            COUNT(*) as attempts,
            SUM(CASE WHEN sta.passed = 1 THEN 1 ELSE 0 END) as passed
        FROM student_test_attempts sta
        JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ?
        AND DATE(sta.created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
        GROUP BY DATE(sta.created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$school_id]);
    $daily_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar -->
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
            </a>
            
            <a href="reports.php" class="nav-item active">
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
        <header class="top-bar">
            <h1>Reports & Analytics</h1>
            <div class="admin-info">
                <span>🏫 <strong><?php echo htmlspecialchars($school_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <!-- Period Filter -->
            <div class="filters-bar" style="margin-bottom: 32px;">
                <form method="GET" class="search-form">
                    <label style="margin-right: 8px; color: #6B7280; font-weight: 600;">Report Period:</label>
                    <select name="period" class="filter-select" onchange="this.form.submit()">
                        <option value="7" <?php echo $date_filter === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30" <?php echo $date_filter === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="90" <?php echo $date_filter === '90' ? 'selected' : ''; ?>>Last 3 Months</option>
                        <option value="365" <?php echo $date_filter === '365' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </form>

                <div style="display: flex; gap: 12px;">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        Print Report
                    </button>
                </div>
            </div>

            <!-- Summary Statistics -->
            <h2 style="margin-bottom: 16px;">School Performance Summary</h2>
            <div class="stats-cards-grid" style="margin-bottom: 40px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_attempts); ?></h3>
                        <p>Test Attempts</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
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
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $avg_score; ?></h3>
                        <p>Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px;">
                
                <!-- Daily Activity Chart -->
                <div class="report-card">
                    <h3>Test Activity (Last 7 Days)</h3>
                    <canvas id="activityChart" height="200"></canvas>
                </div>

                <!-- Pass Rate Pie Chart -->
                <div class="report-card">
                    <h3>Overall Pass/Fail Distribution</h3>
                    <canvas id="passRateChart" height="200"></canvas>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px;">
                
                <!-- Top Performers -->
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">🏆 Top Performing Students</h2>
                    <?php if (empty($top_students)): ?>
                        <div class="empty-state">
                            <p>No test data available</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student</th>
                                    <th>Tests</th>
                                    <th>Avg Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($top_students as $student): ?>
                                    <tr>
                                        <td>
                                            <?php if ($rank <= 3): ?>
                                                <span style="font-size: 20px;">
                                                    <?php echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); ?>
                                                </span>
                                            <?php else: ?>
                                                <strong><?php echo $rank; ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td><?php echo $student['total_tests']; ?></td>
                                        <td><strong style="color: #10B981;"><?php echo round($student['avg_score'], 1); ?></strong></td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Students Who Need Help -->
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">⚠️ Students Needing Support</h2>
                    <?php if (empty($struggling_students)): ?>
                        <div class="empty-state">
                            <p style="color: #10B981;">All students are performing well! 🎉</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Total Tests</th>
                                    <th>Failed</th>
                                    <th>Avg Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($struggling_students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td><?php echo $student['total_tests']; ?></td>
                                        <td><span style="color: #EF4444; font-weight: 600;"><?php echo $student['failed_tests']; ?></span></td>
                                        <td><?php echo round($student['avg_score'], 1); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Test Performance Breakdown -->
            <div class="table-container">
                <h2 style="margin-bottom: 16px;">Test Performance Breakdown</h2>
                <?php if (empty($test_performance)): ?>
                    <div class="empty-state">
                        <p>No test data available</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Test Code</th>
                                <th>Test Name</th>
                                <th>Attempts</th>
                                <th>Passed</th>
                                <th>Pass Rate</th>
                                <th>Avg Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($test_performance as $test): ?>
                                <?php 
                                $test_pass_rate = $test['attempts'] > 0 ? round(($test['passed'] / $test['attempts']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($test['test_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($test['name_en']); ?></td>
                                    <td><?php echo $test['attempts']; ?></td>
                                    <td><?php echo $test['passed']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $test_pass_rate >= 70 ? '#10B981' : ($test_pass_rate >= 50 ? '#F59E0B' : '#EF4444'); ?>; font-weight: 600;">
                                            <?php echo $test_pass_rate; ?>%
                                        </span>
                                    </td>
                                    <td><strong><?php echo round($test['avg_score'], 1); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        // Daily Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityData = <?php echo json_encode($daily_activity); ?>;
        
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: activityData.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Total Attempts',
                    data: activityData.map(d => d.attempts),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Passed',
                    data: activityData.map(d => d.passed),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Pass Rate Pie Chart
        const passRateCtx = document.getElementById('passRateChart').getContext('2d');
        
        new Chart(passRateCtx, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{
                    data: [<?php echo $pass_data['passed']; ?>, <?php echo $pass_data['total'] - $pass_data['passed']; ?>],
                    backgroundColor: ['#10B981', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <style>
        @media print {
            .sidebar, .top-bar .admin-info, .filters-bar, .btn {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</body>
</html>