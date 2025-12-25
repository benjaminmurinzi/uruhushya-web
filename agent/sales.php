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
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["s.agent_id = ?"];
$params = [$agent_id];

if (!empty($status_filter)) {
    $where_conditions[] = "s.commission_status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_from)) {
    $where_conditions[] = "s.sale_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "s.sale_date <= ?";
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM agent_sales s
    JOIN users u ON s.customer_id = u.id
    $where_clause
";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_sales = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_sales / $per_page);

// Get sales
$query = "
    SELECT 
        s.*,
        u.full_name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        u.user_type as customer_type
    FROM agent_sales s
    JOIN users u ON s.customer_id = u.id
    $where_clause
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(sale_amount) as total_sales_amount,
        SUM(commission_amount) as total_commission
    FROM agent_sales 
    WHERE agent_id = ?
");
$stmt->execute([$agent_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM agent_sales WHERE agent_id = ? AND commission_status = 'pending'");
$stmt->execute([$agent_id]);
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM agent_sales WHERE agent_id = ? AND commission_status = 'approved'");
$stmt->execute([$agent_id]);
$approved_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM agent_sales WHERE agent_id = ? AND commission_status = 'paid'");
$stmt->execute([$agent_id]);
$paid_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - URUHUSHYA Agent</title>
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
            
            <a href="sales.php" class="nav-item active">
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
            
            <a href="customers.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Customers
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
            <h1>Sales Management</h1>
            <div class="admin-info">
                <span>💼 <strong><?php echo htmlspecialchars($agent_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'recorded'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Sale recorded successfully!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_sales_amount']); ?> RWF</h3>
                        <p>Total Sales Value</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_commission']); ?> RWF</h3>
                        <p>Total Commission</p>
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
                        <h3><?php echo number_format($pending_count); ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-bar">
                <form method="GET" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by customer name, email, or phone..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    >
                    
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>

                    <input 
                        type="date" 
                        name="date_from" 
                        value="<?php echo htmlspecialchars($date_from); ?>"
                        class="filter-select"
                        placeholder="From Date"
                    >

                    <input 
                        type="date" 
                        name="date_to" 
                        value="<?php echo htmlspecialchars($date_to); ?>"
                        class="filter-select"
                        placeholder="To Date"
                    >
                    
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                    
                    <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <a href="sales.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Sales Table -->
            <div class="table-container">
                <?php if (empty($sales)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <h3>No sales found</h3>
                        <p>Your sales will appear here once recorded</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Sale Date</th>
                                <th>Sale Amount</th>
                                <th>Commission Rate</th>
                                <th>Commission</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><strong>#<?php echo $sale['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong>
                                        <br>
                                        <small style="color: #6B7280;"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #DBEAFE; color: #1E40AF;">
                                            <?php echo ucfirst($sale['customer_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                    <td><strong><?php echo number_format($sale['sale_amount']); ?> RWF</strong></td>
                                    <td><?php echo $sale['commission_rate']; ?>%</td>
                                    <td style="color: #6366F1; font-weight: 600;">
                                        <?php echo number_format($sale['commission_amount']); ?> RWF
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $sale['commission_status']; ?>">
                                            <?php echo ucfirst($sale['commission_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="sale-view.php?id=<?php echo $sale['id']; ?>" class="btn-icon" title="View">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
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