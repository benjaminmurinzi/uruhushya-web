<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($transaction_id <= 0) {
    header('Location: transactions.php?error=invalid_transaction');
    exit;
}

// Get transaction details with user info
try {
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name, u.email, u.phone, u.user_type,
               s.plan_name, s.start_date, s.end_date
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN subscriptions s ON t.subscription_id = s.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        header('Location: transactions.php?error=transaction_not_found');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Transaction view error: " . $e->getMessage());
    header('Location: transactions.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - URUHUSHYA Admin</title>
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
            
            <a href="transactions.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Transactions
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
            <h1>Transaction Details</h1>
            <div class="admin-info">
                <a href="transactions.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Transactions
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="user-profile-card">
                <!-- Transaction Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($transaction['transaction_ref']); ?></h2>
                        <p class="user-type">
                            <span class="status-badge <?php echo $transaction['status']; ?>">
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <?php if ($transaction['status'] === 'pending'): ?>
                            <form method="POST" action="transaction-approve.php" style="display: inline;">
                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                <button type="submit" class="btn btn-success">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Approve Transaction
                                </button>
                            </form>
                            
                            <form method="POST" action="transaction-reject.php" style="display: inline;">
                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    Reject Transaction
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Transaction Details -->
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Transaction ID</label>
                        <p><strong>#<?php echo $transaction['id']; ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Reference Number</label>
                        <p><strong><?php echo htmlspecialchars($transaction['transaction_ref']); ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Amount</label>
                        <p><strong><?php echo number_format($transaction['amount']); ?> <?php echo $transaction['currency']; ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Payment Method</label>
                        <p><?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Payment Provider</label>
                        <p><?php echo htmlspecialchars($transaction['payment_provider'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Payment Phone</label>
                        <p><?php echo htmlspecialchars($transaction['payment_phone'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Transaction Date</label>
                        <p><?php echo date('F d, Y H:i:s', strtotime($transaction['created_at'])); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Status</label>
                        <p>
                            <span class="status-badge <?php echo $transaction['status']; ?>">
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- User Information -->
                <div style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px;">Customer Information</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($transaction['full_name']); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($transaction['email']); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <label>Phone</label>
                            <p><?php echo htmlspecialchars($transaction['phone']); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <label>User Type</label>
                            <p><?php echo ucfirst($transaction['user_type']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Subscription Information -->
                <?php if ($transaction['subscription_id']): ?>
                    <div style="margin-top: 32px;">
                        <h3 style="margin-bottom: 16px;">Subscription Information</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Plan Name</label>
                                <p><?php echo htmlspecialchars($transaction['plan_name']); ?></p>
                            </div>
                            
                            <div class="detail-item">
                                <label>Start Date</label>
                                <p><?php echo date('F d, Y', strtotime($transaction['start_date'])); ?></p>
                            </div>
                            
                            <div class="detail-item">
                                <label>End Date</label>
                                <p><?php echo date('F d, Y', strtotime($transaction['end_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Description & Notes -->
                <?php if (!empty($transaction['description']) || !empty($transaction['admin_notes'])): ?>
                    <div style="margin-top: 32px;">
                        <?php if (!empty($transaction['description'])): ?>
                            <div style="margin-bottom: 16px;">
                                <h4 style="margin-bottom: 8px;">Description</h4>
                                <p style="color: #6B7280;"><?php echo nl2br(htmlspecialchars($transaction['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction['admin_notes'])): ?>
                            <div style="padding: 16px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 8px;">
                                <h4 style="margin-bottom: 8px; color: #92400E;">Admin Notes</h4>
                                <p style="color: #92400E;"><?php echo nl2br(htmlspecialchars($transaction['admin_notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>