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
$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sale_id <= 0) {
    header('Location: sales.php?error=invalid_sale');
    exit;
}

// Get sale details
try {
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            u.user_type as customer_type,
            sub.plan_name,
            sub.start_date as subscription_start,
            sub.end_date as subscription_end,
            t.transaction_ref,
            t.payment_method,
            admin.full_name as approved_by_name
        FROM agent_sales s
        JOIN users u ON s.customer_id = u.id
        LEFT JOIN subscriptions sub ON s.subscription_id = sub.id
        LEFT JOIN transactions t ON s.transaction_id = t.id
        LEFT JOIN users admin ON s.approved_by = admin.id
        WHERE s.id = ? AND s.agent_id = ?
    ");
    $stmt->execute([$sale_id, $agent_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        header('Location: sales.php?error=sale_not_found');
        exit;
    }
} catch (Exception $e) {
    error_log("View sale error: " . $e->getMessage());
    header('Location: sales.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Details - URUHUSHYA Agent</title>
    <link rel="stylesheet" href="../assets/css/agent-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as sales.php) -->
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
            <h1>Sale Details</h1>
            <div class="admin-info">
                <a href="sales.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Sales
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="user-profile-card">
                <!-- Sale Header -->
                <div class="profile-header">
                    <div class="profile-avatar" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                        #<?php echo $sale['id']; ?>
                    </div>
                    <div class="profile-info">
                        <h2>Sale #<?php echo $sale['id']; ?></h2>
                        <p class="user-type">
                            <span class="status-badge <?php echo $sale['commission_status']; ?>">
                                <?php echo ucfirst($sale['commission_status']); ?>
                            </span>
                        </p>
                        <p style="color: #6B7280; margin-top: 4px;">
                            Sale Date: <?php echo date('F d, Y', strtotime($sale['sale_date'])); ?>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #6366F1;">
                                <?php echo number_format($sale['commission_amount']); ?> RWF
                            </div>
                            <div style="color: #6B7280; font-size: 14px;">Your Commission</div>
                        </div>
                    </div>
                </div>

                <!-- Sale Details -->
                <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Sale Information</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Sale Amount</label>
                        <p style="font-size: 24px; font-weight: 700; color: #10B981;">
                            <?php echo number_format($sale['sale_amount']); ?> RWF
                        </p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Commission Rate</label>
                        <p><strong><?php echo $sale['commission_rate']; ?>%</strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Commission Amount</label>
                        <p style="font-size: 24px; font-weight: 700; color: #6366F1;">
                            <?php echo number_format($sale['commission_amount']); ?> RWF
                        </p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Commission Status</label>
                        <p>
                            <span class="status-badge <?php echo $sale['commission_status']; ?>">
                                <?php echo ucfirst($sale['commission_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Customer Details -->
                <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Customer Information</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Customer Name</label>
                        <p><strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Customer Type</label>
                        <p><?php echo ucfirst($sale['customer_type']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Phone</label>
                        <p><?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($sale['customer_email']); ?></p>
                    </div>
                </div>

                <!-- Subscription Details -->
                <?php if ($sale['subscription_id']): ?>
                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Subscription Details</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Plan Name</label>
                            <p><strong><?php echo htmlspecialchars($sale['plan_name']); ?></strong></p>
                        </div>
                        
                        <div class="detail-item">
                            <label>Start Date</label>
                            <p><?php echo date('M d, Y', strtotime($sale['subscription_start'])); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <label>End Date</label>
                            <p><?php echo date('M d, Y', strtotime($sale['subscription_end'])); ?></p>
                        </div>
                        
                        <?php if ($sale['transaction_ref']): ?>
                            <div class="detail-item">
                                <label>Transaction Ref</label>
                                <p><strong><?php echo htmlspecialchars($sale['transaction_ref']); ?></strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Approval Details -->
                <?php if ($sale['commission_status'] !== 'pending'): ?>
                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Approval Information</h3>
                    <div class="details-grid">
                        <?php if ($sale['approved_by']): ?>
                            <div class="detail-item">
                                <label>Approved By</label>
                                <p><?php echo htmlspecialchars($sale['approved_by_name']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($sale['approved_at']): ?>
                            <div class="detail-item">
                                <label>Approved At</label>
                                <p><?php echo date('M d, Y H:i', strtotime($sale['approved_at'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($sale['paid_at']): ?>
                            <div class="detail-item">
                                <label>Paid At</label>
                                <p><?php echo date('M d, Y H:i', strtotime($sale['paid_at'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if (!empty($sale['notes'])): ?>
                    <div style="margin-top: 32px; padding: 16px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 8px;">
                        <h4 style="margin-bottom: 8px; color: #92400E;">Notes</h4>
                        <p style="color: #92400E;"><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>