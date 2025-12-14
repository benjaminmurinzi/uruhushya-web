<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];

// Get statistics
try {
    // Total students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'student'");
    $stmt->execute();
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total schools
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'school'");
    $stmt->execute();
    $total_schools = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total agents
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'agent'");
    $stmt->execute();
    $total_agents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total questions
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM questions");
    $stmt->execute();
    $total_questions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total tests
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tests");
    $stmt->execute();
    $total_tests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total test attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts");
    $stmt->execute();
    $total_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent registrations (last 7 days)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $recent_registrations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active this week
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $active_tests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending school approvals
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'school' AND status = 'pending'");
    $stmt->execute();
    $pending_schools = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending agent approvals
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'agent' AND status = 'pending'");
    $stmt->execute();
    $pending_agents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $total_students = $total_schools = $total_agents = $total_questions = $total_tests = $total_attempts = $recent_registrations = $active_tests = $pending_schools = $pending_agents = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - URUHUSHYA</title>
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
            <a href="dashboard.php" class="nav-item active">
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
                <?php if (($pending_schools + $pending_agents) > 0): ?>
                    <span class="badge"><?php echo ($pending_schools + $pending_agents); ?></span>
                <?php endif; ?>
            </a>
            
            <a href="tests.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
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
            
            <a href="requests.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Requests
                <?php if (($pending_schools + $pending_agents) > 0): ?>
                    <span class="badge"><?php echo ($pending_schools + $pending_agents); ?></span>
                <?php endif; ?>
            </a>
            
            <a href="analytics.php" class="nav-item">
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
                    <path d="M12 1v6m0 6v6m-9-9h6m6 0h6"></path>
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
        <!-- Top Bar -->
        <header class="top-bar">
            <h1>Dashboard Overview</h1>
            <div class="admin-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </header>

        <!-- Stats Grid -->
        <section class="stats-section">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_students); ?></h3>
                    <p>Total Students</p>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_schools); ?></h3>
                    <p>Total Schools</p>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_agents); ?></h3>
                    <p>Total Agents</p>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_tests); ?></h3>
                    <p>Total Tests</p>
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_questions); ?></h3>
                    <p>Question Bank</p>
                </div>
            </div>

            <div class="stat-card teal">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_attempts); ?></h3>
                    <p>Test Attempts</p>
                </div>
            </div>

            <div class="stat-card indigo">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($recent_registrations); ?></h3>
                    <p>New Users (7 days)</p>
                </div>
            </div>

            <div class="stat-card pink">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($active_tests); ?></h3>
                    <p>Active This Week</p>
                </div>
            </div>

            <?php if (($pending_schools + $pending_agents) > 0): ?>
            <div class="stat-card warning">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($pending_schools + $pending_agents); ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="users.php?tab=students&action=new" class="action-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <h3>Add New User</h3>
                    <p>Create student, school, or agent account</p>
                </a>

                <a href="tests.php?action=new" class="action-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <h3>Create New Test</h3>
                    <p>Add a new test to the system</p>
                </a>

                <a href="questions.php?action=new" class="action-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3>Add Question</h3>
                    <p>Add new question to question bank</p>
                </a>

                <a href="requests.php" class="action-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <h3>Review Requests</h3>
                    <p>Approve pending registrations</p>
                    <?php if (($pending_schools + $pending_agents) > 0): ?>
                        <span class="action-badge"><?php echo ($pending_schools + $pending_agents); ?></span>
                    <?php endif; ?>
                </a>

                <a href="subscriptions.php?action=new" class="action-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <h3>Create Subscription</h3>
                    <p>Manual subscription for offline payment</p>
                </a>

                <a href="analytics.php" class="action-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <h3>View Analytics</h3>
                    <p>System reports and insights</p>
                </a>
            </div>
        </section>
    </main>

</body>
</html>