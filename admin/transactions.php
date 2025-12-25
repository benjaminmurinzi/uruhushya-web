<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$method_filter = $_GET['method'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if (!empty($method_filter)) {
    $where_conditions[] = "t.payment_method = ?";
    $params[] = $method_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR t.transaction_ref LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    $where_clause
";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_transactions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$query = "
    SELECT t.*, u.full_name, u.email
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    $where_clause
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'pending'");
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'failed'");
$failed_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
    <!-- Sidebar (same structure) -->
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
            
            <a href="transactions.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Transactions
                <?php if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
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
            <h1>Payment Transactions</h1>
            <div class="admin-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'approved'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Transaction approved successfully!
                </div>
            <?php elseif ($success === 'rejected'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Transaction rejected successfully!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($pending_count); ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_revenue); ?> RWF</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($failed_count); ?></h3>
                        <p>Failed Transactions</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-bar">
                <form method="GET" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by user, email, or transaction ref..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    >
                    
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>

                    <select name="method" class="filter-select">
                        <option value="">All Methods</option>
                        <option value="momo" <?php echo $method_filter === 'momo' ? 'selected' : ''; ?>>Mobile Money</option>
                        <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="cash" <?php echo $method_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="card" <?php echo $method_filter === 'card' ? 'selected' : ''; ?>>Card</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                    
                    <?php if (!empty($search) || !empty($status_filter) || !empty($method_filter)): ?>
                        <a href="transactions.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="table-container">
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <h3>No transactions found</h3>
                        <p>Transaction history will appear here</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction Ref</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($txn['transaction_ref']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($txn['full_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($txn['email']); ?></small>
                                    </td>
                                    <td><strong><?php echo number_format($txn['amount']); ?> RWF</strong></td>
                                    <td>
                                        <span class="status-badge pending">
                                            <?php echo ucfirst(str_replace('_', ' ', $txn['payment_method'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $txn['status']; ?>">
                                            <?php echo ucfirst($txn['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="transaction-view.php?id=<?php echo $txn['id']; ?>" class="btn-icon" title="View">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            
                                            <?php if ($txn['status'] === 'pending'): ?>
                                                <form method="POST" action="transaction-approve.php" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $txn['id']; ?>">
                                                    <button type="submit" class="btn-icon btn-success" title="Approve">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" action="transaction-reject.php" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $txn['id']; ?>">
                                                    <button type="submit" class="btn-icon btn-danger" title="Reject"
                                                            onclick="return confirm('Are you sure you want to reject this transaction?')">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&method=<?php echo $method_filter; ?>">
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