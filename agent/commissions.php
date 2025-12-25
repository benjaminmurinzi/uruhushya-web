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

// Get commission statistics
try {
    // Total commissions breakdown
    $stmt = $conn->prepare("
        SELECT 
            SUM(commission_amount) as total,
            SUM(CASE WHEN commission_status = 'pending' THEN commission_amount ELSE 0 END) as pending,
            SUM(CASE WHEN commission_status = 'approved' THEN commission_amount ELSE 0 END) as approved,
            SUM(CASE WHEN commission_status = 'paid' THEN commission_amount ELSE 0 END) as paid,
            SUM(CASE WHEN commission_status = 'rejected' THEN commission_amount ELSE 0 END) as rejected
        FROM agent_sales 
        WHERE agent_id = ?
    ");
    $stmt->execute([$agent_id]);
    $commission_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all commissions
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.full_name as customer_name,
            u.user_type as customer_type
        FROM agent_sales s
        JOIN users u ON s.customer_id = u.id
        WHERE s.agent_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$agent_id]);
    $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payout requests
    $stmt = $conn->prepare("
        SELECT * FROM agent_commission_requests 
        WHERE agent_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$agent_id]);
    $payout_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get agent payment details
    $stmt = $conn->prepare("SELECT * FROM agent_profile WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Commissions error: " . $e->getMessage());
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commissions - URUHUSHYA Agent</title>
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
            
            <a href="clients.php" class="nav-item">
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
            
            <a href="commissions.php" class="nav-item active">
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
            <h1>Commission Management</h1>
            <div class="admin-info">
                <span>💼 <strong><?php echo htmlspecialchars($agent_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'request_submitted'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Payout request submitted successfully! Awaiting admin approval.
                </div>
            <?php endif; ?>
            
            <?php if ($error === 'no_payment_details'): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Please add your payment details in your profile before requesting payout.
                </div>
            <?php elseif ($error === 'no_approved_commission'): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    You don't have any approved commissions available for withdrawal.
                </div>
            <?php endif; ?>

            <!-- Commission Summary Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($commission_stats['total'] ?? 0); ?> RWF</h3>
                        <p>Total Earned</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($commission_stats['approved'] ?? 0); ?> RWF</h3>
                        <p>Available to Withdraw</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($commission_stats['paid'] ?? 0); ?> RWF</h3>
                        <p>Paid Out</p>
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
                        <h3><?php echo number_format($commission_stats['pending'] ?? 0); ?> RWF</h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
            </div>

            <!-- Request Payout Section -->
            <?php if ($commission_stats['approved'] > 0): ?>
                <div class="commission-request-card" style="margin-bottom: 32px;">
                    <h3>💰 Ready to Withdraw</h3>
                    <p style="margin-bottom: 16px; color: #1E40AF;">
                        You have <strong style="font-size: 24px;"><?php echo number_format($commission_stats['approved']); ?> RWF</strong> in approved commissions ready for withdrawal.
                    </p>
                    
                    <?php if (!$agent_profile || (empty($agent_profile['momo_number']) && empty($agent_profile['account_number']))): ?>
                        <div class="alert alert-warning" style="margin-bottom: 16px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            Please add your payment details in your profile before requesting payout.
                        </div>
                        <a href="profile.php" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Update Payment Details
                        </a>
                    <?php else: ?>
                        <a href="request-payout.php" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Request Payout
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs" style="margin-bottom: 24px;">
                <button class="tab-btn active" onclick="switchTab('commissions')">
                    Commission History
                </button>
                <button class="tab-btn" onclick="switchTab('payouts')">
                    Payout Requests
                </button>
            </div>

            <!-- Commission History Tab -->
            <div id="commissionsTab" class="tab-content active">
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">Commission History</h2>
                    <?php if (empty($commissions)): ?>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <h3>No commissions yet</h3>
                            <p>Register clients and create subscriptions to start earning</p>
                            <a href="register-client.php" class="btn btn-primary" style="margin-top: 16px;">
                                Register Your First Client
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Sale Date</th>
                                    <th>Sale Amount</th>
                                    <th>Rate</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                    <th>Paid Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commissions as $comm): ?>
                                    <tr>
                                        <td><strong>#<?php echo $comm['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($comm['customer_name']); ?></strong>
                                            <br>
                                            <small style="color: #6B7280;"><?php echo ucfirst($comm['customer_type']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($comm['sale_date'])); ?></td>
                                        <td><?php echo number_format($comm['sale_amount']); ?> RWF</td>
                                        <td><?php echo $comm['commission_rate']; ?>%</td>
                                        <td style="color: #6366F1; font-weight: 600;">
                                            <?php echo number_format($comm['commission_amount']); ?> RWF
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $comm['commission_status']; ?>">
                                                <?php echo ucfirst($comm['commission_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($comm['paid_at']): ?>
                                                <?php echo date('M d, Y', strtotime($comm['paid_at'])); ?>
                                            <?php else: ?>
                                                <span style="color: #9CA3AF;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payout Requests Tab -->
            <div id="payoutsTab" class="tab-content">
                <div class="table-container">
                    <h2 style="margin-bottom: 16px;">Payout Requests</h2>
                    <?php if (empty($payout_requests)): ?>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <h3>No payout requests yet</h3>
                            <p>Request a payout when you have approved commissions</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Processed Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payout_requests as $request): ?>
                                    <tr>
                                        <td><strong>#<?php echo $request['id']; ?></strong></td>
                                        <td style="font-weight: 600; color: #6366F1;">
                                            <?php echo number_format($request['request_amount']); ?> RWF
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $request['payment_method'])); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['processed_at']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($request['processed_at'])); ?>
                                            <?php else: ?>
                                                <span style="color: #9CA3AF;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['admin_notes']): ?>
                                                <small><?php echo htmlspecialchars($request['admin_notes']); ?></small>
                                            <?php else: ?>
                                                <span style="color: #9CA3AF;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            if (tab === 'commissions') {
                document.getElementById('commissionsTab').classList.add('active');
                event.target.classList.add('active');
            } else if (tab === 'payouts') {
                document.getElementById('payoutsTab').classList.add('active');
                event.target.classList.add('active');
            }
        }
    </script>

    <style>
        .tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #6B7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            color: #6366F1;
        }
        
        .tab-btn.active {
            color: #6366F1;
            border-bottom-color: #6366F1;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</body>
</html>