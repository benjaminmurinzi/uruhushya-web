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
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($client_id <= 0) {
    header('Location: clients.php?error=invalid_client');
    exit;
}

// Get client details
try {
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE id = ? AND referred_by = ?
    ");
    $stmt->execute([$client_id, $agent_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        header('Location: clients.php?error=client_not_found');
        exit;
    }
    
    // Get client subscriptions
    $stmt = $conn->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$client_id]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get commission earned from this client
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_sales,
            SUM(sale_amount) as total_sales_value,
            SUM(commission_amount) as total_commission,
            SUM(CASE WHEN commission_status = 'paid' THEN commission_amount ELSE 0 END) as paid_commission,
            SUM(CASE WHEN commission_status = 'approved' THEN commission_amount ELSE 0 END) as approved_commission,
            SUM(CASE WHEN commission_status = 'pending' THEN commission_amount ELSE 0 END) as pending_commission
        FROM agent_sales 
        WHERE agent_id = ? AND customer_id = ?
    ");
    $stmt->execute([$agent_id, $client_id]);
    $commission_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("View client error: " . $e->getMessage());
    header('Location: clients.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - URUHUSHYA Agent</title>
    <link rel="stylesheet" href="../assets/css/agent-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as clients.php) -->
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
            <h1>Client Details</h1>
            <div class="admin-info">
                <a href="clients.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Clients
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="user-profile-card">
                <!-- Client Header -->
                <div class="profile-header">
                    <div class="profile-avatar" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                        <?php 
                        $initials = strtoupper(substr($client['full_name'], 0, 2));
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($client['full_name']); ?></h2>
                        <p class="user-type">
                            <span class="badge" style="background: <?php echo $client['user_type'] === 'student' ? '#DBEAFE' : '#D1FAE5'; ?>; color: <?php echo $client['user_type'] === 'student' ? '#1E40AF' : '#065F46'; ?>;">
                                <?php echo ucfirst($client['user_type']); ?>
                            </span>
                        </p>
                        <p style="color: #6B7280; margin-top: 8px;">
                            Registered: <?php echo date('F d, Y', strtotime($client['created_at'])); ?>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <a href="create-subscription.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Subscription
                        </a>
                    </div>
                </div>

                <!-- Client Details -->
                <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Contact Information</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($client['phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($client['email'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Account Status</label>
                        <p>
                            <span class="status-badge <?php echo $client['status']; ?>">
                                <?php echo ucfirst($client['status']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Client Type</label>
                        <p><?php echo ucfirst($client['user_type']); ?></p>
                    </div>
                </div>

                <!-- Commission Statistics -->
                <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Your Earnings from this Client</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4><?php echo number_format($commission_stats['total_commission'] ?? 0); ?> RWF</h4>
                        <p>Total Commission</p>
                    </div>
                    
                    <div class="stat-box">
                        <h4><?php echo number_format($commission_stats['paid_commission'] ?? 0); ?> RWF</h4>
                        <p>Paid Out</p>
                    </div>
                    
                    <div class="stat-box">
                        <h4><?php echo number_format($commission_stats['approved_commission'] ?? 0); ?> RWF</h4>
                        <p>Approved</p>
                    </div>
                    
                    <div class="stat-box">
                        <h4><?php echo number_format($commission_stats['pending_commission'] ?? 0); ?> RWF</h4>
                        <p>Pending</p>
                    </div>
                    
                    <div class="stat-box">
                        <h4><?php echo $commission_stats['total_sales'] ?? 0; ?></h4>
                        <p>Total Sales</p>
                    </div>
                </div>

                <!-- Subscription History -->
                <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Subscription History</h3>
                <?php if (empty($subscriptions)): ?>
                    <div class="empty-state">
                        <p>No subscriptions yet</p>
                        <a href="create-subscription.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary" style="margin-top: 16px;">
                            Create First Subscription
                        </a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sub['plan_name']); ?></strong></td>
                                    <td><?php echo number_format($sub['amount']); ?> RWF</td>
                                    <td><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $sub['status']; ?>">
                                            <?php echo ucfirst($sub['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $sub['payment_method'])); ?></td>
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
