<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: users.php?error=invalid_user');
    exit;
}

// Get user details
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: users.php?error=user_not_found');
        exit;
    }
    
    // Get user statistics based on user type
    if ($user['user_type'] === 'student') {
        // Get test attempts
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE student_id = ?");
        $stmt->execute([$user_id]);
        $total_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get passed tests
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE student_id = ? AND passed = 1");
        $stmt->execute([$user_id]);
        $passed_tests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get recent attempts
        $stmt = $conn->prepare("
            SELECT sta.*, t.test_code, t.test_name 
            FROM student_test_attempts sta
            JOIN tests t ON sta.test_id = t.id
            WHERE sta.student_id = ?
            ORDER BY sta.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("User view error: " . $e->getMessage());
    header('Location: users.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as users.php) -->
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
            
            <a href="users.php" class="nav-item active">
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
        <!-- Top Bar -->
        <header class="top-bar">
            <h1>User Details</h1>
            <div class="admin-info">
                <a href="users.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Users
                </a>
            </div>
        </header>

        <!-- Content -->
        <section class="content-section">
            <div class="user-profile-card">
                <!-- User Info -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <span class="status-badge <?php echo $user['status']; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                        <p class="user-type"><?php echo ucfirst($user['user_type']); ?></p>
                    </div>
                    <div class="profile-actions">
                        <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit User
                        </a>
                        
                        <?php if ($user['status'] === 'pending'): ?>
                            <a href="user-approve.php?id=<?php echo $user['id']; ?>" class="btn btn-success">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Approve User
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Details Grid -->
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Phone</label>
                        <p><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>User Type</label>
                        <p><?php echo ucfirst($user['user_type']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Status</label>
                        <p><?php echo ucfirst($user['status']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Joined Date</label>
                        <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Last Login</label>
                        <p><?php echo $user['last_login'] ? date('F d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
                    </div>
                </div>

                <!-- Student Statistics -->
                <?php if ($user['user_type'] === 'student'): ?>
                    <div class="user-stats">
                        <h3>Student Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <h4><?php echo $total_attempts; ?></h4>
                                <p>Total Attempts</p>
                            </div>
                            <div class="stat-box">
                                <h4><?php echo $passed_tests; ?></h4>
                                <p>Tests Passed</p>
                            </div>
                            <div class="stat-box">
                                <h4><?php echo $total_attempts > 0 ? round(($passed_tests / $total_attempts) * 100) : 0; ?>%</h4>
                                <p>Success Rate</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Test Attempts -->
                    <?php if (!empty($recent_attempts)): ?>
                        <div class="recent-attempts">
                            <h3>Recent Test Attempts</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Test</th>
                                        <th>Score</th>
                                        <th>Result</th>
                                        <th>Date</th>
                                        <th>Time Taken</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_attempts as $attempt): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($attempt['test_code']); ?></strong></td>
                                            <td><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></td>
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
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>