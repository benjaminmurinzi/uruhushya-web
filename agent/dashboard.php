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

// Get agent statistics
try {
    // Total sales
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM agent_sales WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $total_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total earnings
    $stmt = $conn->prepare("
        SELECT 
            SUM(commission_amount) as total_earned,
            SUM(CASE WHEN commission_status = 'paid' THEN commission_amount ELSE 0 END) as paid,
            SUM(CASE WHEN commission_status = 'approved' THEN commission_amount ELSE 0 END) as approved,
            SUM(CASE WHEN commission_status = 'pending' THEN commission_amount ELSE 0 END) as pending
        FROM agent_sales 
        WHERE agent_id = ?
    ");
    $stmt->execute([$agent_id]);
    $earnings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Sales this month
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM agent_sales 
        WHERE agent_id = ? 
        AND MONTH(sale_date) = MONTH(CURRENT_DATE())
        AND YEAR(sale_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$agent_id]);
    $sales_this_month = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending commission requests
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM agent_commission_requests 
        WHERE agent_id = ? AND status = 'pending'
    ");
    $stmt->execute([$agent_id]);
    $pending_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent sales
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.full_name as customer_name,
            u.user_type as customer_type
        FROM agent_sales s
        JOIN users u ON s.customer_id = u.id
        WHERE s.agent_id = ?
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$agent_id]);
    $recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top customers
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            u.user_type,
            COUNT(s.id) as total_purchases,
            SUM(s.sale_amount) as total_spent,
            SUM(s.commission_amount) as total_commission
        FROM agent_sales s
        JOIN users u ON s.customer_id = u.id
        WHERE s.agent_id = ?
        GROUP BY s.customer_id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $stmt->execute([$agent_id]);
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get agent profile
    $stmt = $conn->prepare("SELECT * FROM agent_profile WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Agent dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - URUHUSHYA</title>
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
            <a href="dashboard.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
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
                <?php if ($pending_requests > 0): ?>
                    <span class="badge"><?php echo $pending_requests; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="clients.php" class="nav-item">
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
            <h1>Dashboard</h1>
            <div class="admin-info">
                <span>💼 <strong><?php echo htmlspecialchars($agent_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <!-- Earnings Overview -->
            <div class="earnings-card" style="margin-bottom: 32px;">
                <h3>Total Earnings</h3>
                <div>
                    <span class="amount"><?php echo number_format($earnings['total_earned'] ?? 0); ?></span>
                    <span class="currency">RWF</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.2);">
                    <div>
                        <div style="font-size: 24px; font-weight: 700;"><?php echo number_format($earnings['paid'] ?? 0); ?></div>
                        <div style="font-size: 13px; opacity: 0.8;">Paid Out</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 700;"><?php echo number_format($earnings['approved'] ?? 0); ?></div>
                        <div style="font-size: 13px; opacity: 0.8;">Approved</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 700;"><?php echo number_format($earnings['pending'] ?? 0); ?></div>
                        <div style="font-size: 13px; opacity: 0.8;">Pending</div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 40px;">
                <div class="stat-card agent-theme">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_sales); ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($sales_this_month); ?></h3>
                        <p>Sales This Month</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($pending_requests); ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($agent_profile['commission_rate'] ?? 10); ?>%</h3>
                        <p>Commission Rate</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <?php if ($earnings['approved'] > 0): ?>
                <div class="commission-request-card" style="margin-bottom: 40px;">
                    <h3>💰 Available to Withdraw</h3>
                    <p style="margin-bottom: 16px; color: #1E40AF;">
                        You have <strong><?php echo number_format($earnings['approved']); ?> RWF</strong> in approved commissions ready for withdrawal.
                    </p>
                    <a href="commissions.php?action=request" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Request Payout
                    </a>
                </div>
            <?php endif; ?>

            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                
                <!-- Recent Sales -->
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">Recent Sales</h2>
                    <?php if (empty($recent_sales)): ?>
                        <div class="empty-state">
                            <p>No sales yet</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong>
                                            <br>
                                            <small style="color: #6B7280;"><?php echo ucfirst($sale['customer_type']); ?></small>
                                        </td>
                                        <td><strong><?php echo number_format($sale['sale_amount']); ?></strong></td>
                                        <td style="color: #6366F1; font-weight: 600;"><?php echo number_format($sale['commission_amount']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $sale['commission_status']; ?>">
                                                <?php echo ucfirst($sale['commission_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Top Customers -->
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">Top Customers</h2>
                    <?php if (empty($top_customers)): ?>
                        <div class="empty-state">
                            <p>No customers yet</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Purchases</th>
                                    <th>Total Spent</th>
                                    <th>Your Earnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                            <br>
                                            <small style="color: #6B7280;"><?php echo ucfirst($customer['user_type']); ?></small>
                                        </td>
                                        <td><?php echo $customer['total_purchases']; ?></td>
                                        <td><strong><?php echo number_format($customer['total_spent']); ?></strong></td>
                                        <td style="color: #6366F1; font-weight: 600;"><?php echo number_format($customer['total_commission']); ?></td>
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