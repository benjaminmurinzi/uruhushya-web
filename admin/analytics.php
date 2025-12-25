<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];

// Get date range (default: last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// User Statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'student'");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'school'");
$total_schools = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'agent'");
$total_agents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// New users in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$new_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Test Statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM test_templates");
$total_tests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM questions");
$total_questions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Test attempts in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE passed = 1 AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$passed_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pass_rate = $total_attempts > 0 ? round(($passed_attempts / $total_attempts) * 100, 1) : 0;

// Revenue Statistics
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'");
$active_subscriptions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Popular Tests
$stmt = $conn->prepare("
    SELECT t.test_code, t.name_en, COUNT(sta.id) as attempt_count
    FROM test_templates t
    LEFT JOIN student_test_attempts sta ON t.id = sta.test_template_id
    WHERE DATE(sta.created_at) BETWEEN ? AND ?
    GROUP BY t.id
    ORDER BY attempt_count DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$popular_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Activity
$stmt = $conn->prepare("
    SELECT u.full_name, t.test_code, sta.score, sta.passed, sta.created_at
    FROM student_test_attempts sta
    JOIN users u ON sta.student_id = u.id
    JOIN test_templates t ON sta.test_template_id = t.id
    WHERE DATE(sta.created_at) BETWEEN ? AND ?
    ORDER BY sta.created_at DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - URUHUSHYA Admin</title>
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
            
            <a href="subscriptions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Subscriptions
            </a>
            
            <a href="transactions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Transactions
            </a>
            
            <a href="analytics.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Analytics
            </a>
            
            <a href="settings.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6"></path>
                </svg>
                Settings
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
            <h1>Analytics & Reports</h1>
            <div class="admin-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <!-- Date Range Filter -->
            <div class="filters-bar" style="margin-bottom: 32px;">
                <form method="GET" class="search-form">
                    <label style="margin-right: 8px; color: #6B7280;">Date Range:</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="<?php echo $start_date; ?>"
                        style="padding: 10px 16px; border: 1px solid #D1D5DB; border-radius: 8px;"
                    >
                    <span style="margin: 0 8px;">to</span>
                    <input 
                        type="date" 
                        name="end_date" 
                        value="<?php echo $end_date; ?>"
                        style="padding: 10px 16px; border: 1px solid #D1D5DB; border-radius: 8px;"
                    >
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                        Update
                    </button>
                </form>
            </div>

            <!-- User Statistics -->
            <h2 style="margin-bottom: 16px;">User Statistics</h2>
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
                        <p>Total Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_schools); ?></h3>
                        <p>Total Schools</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_agents); ?></h3>
                        <p>Total Agents</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($new_users); ?></h3>
                        <p>New Users (Selected Period)</p>
                    </div>
                </div>
            </div>

            <!-- Test Statistics -->
            <h2 style="margin-bottom: 16px;">Test Statistics</h2>
            <div class="stats-cards-grid" style="margin-bottom: 40px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_tests); ?></h3>
                        <p>Total Tests</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_questions); ?></h3>
                        <p>Question Bank</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_attempts); ?></h3>
                        <p>Test Attempts (Selected Period)</p>
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
            </div>

            <!-- Revenue Statistics -->
            <h2 style="margin-bottom: 16px;">Revenue Statistics</h2>
            <div class="stats-cards-grid" style="margin-bottom: 40px;">
                <div class="stat-card" style="grid-column: span 2;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($revenue); ?> RWF</h3>
                        <p>Revenue (Selected Period)</p>
                    </div>
                </div>

                <div class="stat-card" style="grid-column: span 2;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($active_subscriptions); ?></h3>
                        <p>Active Subscriptions</p>
                    </div>
                </div>
            </div>

            <!-- Popular Tests -->
            <h2 style="margin-bottom: 16px;">Most Popular Tests</h2>
            <div class="table-container" style="margin-bottom: 40px;">
                <?php if (empty($popular_tests)): ?>
                    <div class="empty-state">
                        <p>No test data available for this period</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Test Code</th>
                                <th>Test Name</th>
                                <th>Attempts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_tests as $test): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($test['test_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($test['name_en']); ?></td>
                                    <td><?php echo number_format($test['attempt_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <h2 style="margin-bottom: 16px;">Recent Test Activity</h2>
            <div class="table-container">
                <?php if (empty($recent_activity)): ?>
                    <div class="empty-state">
                        <p>No recent activity for this period</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Test</th>
                                <th>Score</th>
                                <th>Result</th>
                                <th>Date</th>
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
                                    <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>