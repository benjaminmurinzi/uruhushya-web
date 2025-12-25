<?php
session_start();
require_once '../config.php';

// Check if agent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$agent_id = $_SESSION['user_id'];
$agent_name = $_SESSION['full_name'];

// Get filter parameters
$type_filter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["u.referred_by = ?"];
$params = [$agent_id];

if (!empty($type_filter)) {
    $where_conditions[] = "u.user_type = ?";
    $params[] = $type_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM users u
    $where_clause
";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_clients = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_clients / $per_page);

// Get clients with subscription info
$query = "
    SELECT 
        u.*,
        COUNT(DISTINCT s.id) as total_subscriptions,
        SUM(s.amount) as total_spent,
        MAX(s.end_date) as latest_subscription_end,
        (SELECT SUM(commission_amount) 
         FROM agent_sales 
         WHERE customer_id = u.id AND agent_id = ?) as total_commission_earned
    FROM users u
    LEFT JOIN subscriptions s ON u.id = s.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $agent_id;
$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE referred_by = ?");
$stmt->execute([$agent_id]);
$total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE referred_by = ? AND user_type = 'student'");
$stmt->execute([$agent_id]);
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE referred_by = ? AND user_type = 'school'");
$stmt->execute([$agent_id]);
$school_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("
    SELECT SUM(commission_amount) as total 
    FROM agent_sales 
    WHERE agent_id = ?
");
$stmt->execute([$agent_id]);
$total_commission = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - URUHUSHYA Agent</title>
    <link rel="stylesheet" href="../assets/css/agent-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>💼 URUHUSHYA</h2>
            <p>Agent Portal</p>
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
            
            <a href="clients.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Clients
            </a>
            
            <a href="sales.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Sales
            </a>
            
            <a href="commissions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Commissions
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
            <h1>Client Management</h1>
            <div class="admin-info">
                <span>💼 <strong><?php echo htmlspecialchars($agent_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'client_registered'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Client registered successfully!
                </div>
            <?php elseif ($success === 'subscription_created'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Subscription created successfully! You'll earn commission once payment is confirmed.
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_count); ?></h3>
                        <p>Total Clients</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($student_count); ?></h3>
                        <p>Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($school_count); ?></h3>
                        <p>Schools</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_commission); ?> RWF</h3>
                        <p>Total Commission</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-bar">
                <form method="GET" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by name, email, or phone..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    >
                    
                    <select name="type" class="filter-select">
                        <option value="">All Types</option>
                        <option value="student" <?php echo $type_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                        <option value="school" <?php echo $type_filter === 'school' ? 'selected' : ''; ?>>Schools</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                    
                    <?php if (!empty($search) || !empty($type_filter)): ?>
                        <a href="clients.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>

                <div>
                    <a href="register-client.php" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Register New Client
                    </a>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="table-container">
                <?php if (empty($clients)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3>No clients yet</h3>
                        <p>Start by registering your first client</p>
                        <a href="register-client.php" class="btn btn-primary" style="margin-top: 16px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Register Client
                        </a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Type</th>
                                <th>Contact</th>
                                <th>Registered Date</th>
                                <th>Subscriptions</th>
                                <th>Total Spent</th>
                                <th>Your Earnings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <?php
                                $is_expired = false;
                                if ($client['latest_subscription_end']) {
                                    $is_expired = strtotime($client['latest_subscription_end']) < time();
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($client['full_name']); ?></strong>
                                        <?php if ($client['latest_subscription_end']): ?>
                                            <br>
                                            <small style="color: <?php echo $is_expired ? '#EF4444' : '#10B981'; ?>;">
                                                <?php echo $is_expired ? '⚠️ Expired' : '✓ Active'; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $client['user_type'] === 'student' ? '#DBEAFE' : '#D1FAE5'; ?>; color: <?php echo $client['user_type'] === 'student' ? '#1E40AF' : '#065F46'; ?>;">
                                            <?php echo ucfirst($client['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($client['phone']); ?></div>
                                        <?php if ($client['email']): ?>
                                            <small style="color: #6B7280;"><?php echo htmlspecialchars($client['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                    <td><strong><?php echo $client['total_subscriptions'] ?? 0; ?></strong></td>
                                    <td>
                                        <?php if ($client['total_spent']): ?>
                                            <strong><?php echo number_format($client['total_spent']); ?> RWF</strong>
                                        <?php else: ?>
                                            <span style="color: #9CA3AF;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #6366F1; font-weight: 600;">
                                        <?php if ($client['total_commission_earned']): ?>
                                            <?php echo number_format($client['total_commission_earned']); ?> RWF
                                        <?php else: ?>
                                            <span style="color: #9CA3AF;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="client-view.php?id=<?php echo $client['id']; ?>" class="btn-icon" title="View">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            
                                            <a href="create-subscription.php?client_id=<?php echo $client['id']; ?>" class="btn-icon" title="Add Subscription" style="color: #10B981;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>