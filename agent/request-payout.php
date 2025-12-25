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
$error = '';

// Get approved commissions
try {
    $stmt = $conn->prepare("
        SELECT SUM(commission_amount) as available_amount
        FROM agent_sales 
        WHERE agent_id = ? AND commission_status = 'approved'
    ");
    $stmt->execute([$agent_id]);
    $available = $stmt->fetch(PDO::FETCH_ASSOC);
    $available_amount = $available['available_amount'] ?? 0;
    
    if ($available_amount <= 0) {
        header('Location: commissions.php?error=no_approved_commission');
        exit;
    }
    
    // Get agent payment details
    $stmt = $conn->prepare("SELECT * FROM agent_profile WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent_profile || (empty($agent_profile['momo_number']) && empty($agent_profile['account_number']))) {
        header('Location: commissions.php?error=no_payment_details');
        exit;
    }
    
    // Get approved sales IDs
    $stmt = $conn->prepare("
        SELECT GROUP_CONCAT(id) as sale_ids
        FROM agent_sales 
        WHERE agent_id = ? AND commission_status = 'approved'
    ");
    $stmt->execute([$agent_id]);
    $sales_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $sales_ids = $sales_data['sale_ids'] ?? '';
    
} catch (Exception $e) {
    error_log("Request payout error: " . $e->getMessage());
    header('Location: commissions.php?error=system_error');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_amount = (float)$_POST['request_amount'];
    $payment_method = $_POST['payment_method'];
    $payment_details = trim($_POST['payment_details']);
    
    if ($request_amount <= 0) {
        $error = 'Invalid amount';
    } elseif ($request_amount > $available_amount) {
        $error = 'Amount exceeds available commission';
    } elseif (empty($payment_method)) {
        $error = 'Please select a payment method';
    } elseif (empty($payment_details)) {
        $error = 'Please provide payment details';
    } else {
        try {
            // Create payout request
            $stmt = $conn->prepare("
                INSERT INTO agent_commission_requests 
                (agent_id, request_amount, payment_method, payment_details, status, sales_included, created_at) 
                VALUES (?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $agent_id,
                $request_amount,
                $payment_method,
                $payment_details,
                $sales_ids
            ]);
            
            // Redirect to success
            header('Location: commissions.php?success=request_submitted');
            exit;
            
        } catch (Exception $e) {
            error_log("Payout request submission error: " . $e->getMessage());
            $error = 'Failed to submit request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Payout - URUHUSHYA Agent</title>
    <link rel="stylesheet" href="../assets/css/agent-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as commissions.php) -->
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
            
            <a href="commissions.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Commissions
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
            <h1>Request Payout</h1>
            <div class="admin-info">
                <a href="commissions.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back
                </a>
            </div>
        </header>

        <section class="content-section">
            <div style="max-width: 800px; margin: 0 auto;">
                
                <!-- Available Amount Card -->
                <div class="earnings-card" style="margin-bottom: 32px;">
                    <h3>Available to Withdraw</h3>
                    <div>
                        <span class="amount"><?php echo number_format($available_amount); ?></span>
                        <span class="currency">RWF</span>
                    </div>
                    <p style="margin-top: 16px; opacity: 0.9; font-size: 14px;">
                        This is your approved commission ready for payout
                    </p>
                </div>

                <!-- Payout Request Form -->
                <div class="form-container">
                    <h2>Payout Request Details</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        
                        <div class="form-group">
                            <label>Request Amount (RWF) *</label>
                            <input 
                                type="number" 
                                name="request_amount" 
                                value="<?php echo $available_amount; ?>"
                                min="1"
                                max="<?php echo $available_amount; ?>"
                                step="1"
                                required
                            >
                            <small>Maximum: <?php echo number_format($available_amount); ?> RWF</small>
                        </div>

                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" id="paymentMethod" required onchange="updatePaymentFields()">
                                <option value="">-- Select payment method --</option>
                                <?php if (!empty($agent_profile['momo_number'])): ?>
                                    <option value="momo">Mobile Money</option>
                                <?php endif; ?>
                                <?php if (!empty($agent_profile['account_number'])): ?>
                                    <option value="bank_transfer">Bank Transfer</option>
                                <?php endif; ?>
                                <option value="cash">Cash Pickup</option>
                            </select>
                        </div>

                        <!-- Mobile Money Details -->
                        <div class="form-group" id="momoDetails" style="display: none;">
                            <label>Mobile Money Number</label>
                            <input 
                                type="text" 
                                name="payment_details" 
                                value="<?php echo htmlspecialchars($agent_profile['momo_number'] ?? ''); ?>"
                                readonly
                                style="background: #F3F4F6;"
                            >
                        </div>

                        <!-- Bank Transfer Details -->
                        <div id="bankDetails" style="display: none;">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input 
                                    type="text" 
                                    value="<?php echo htmlspecialchars($agent_profile['bank_name'] ?? ''); ?>"
                                    readonly
                                    style="background: #F3F4F6;"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label>Account Number</label>
                                <input 
                                    type="text" 
                                    name="payment_details" 
                                    value="<?php echo htmlspecialchars($agent_profile['account_number'] ?? ''); ?>"
                                    readonly
                                    style="background: #F3F4F6;"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label>Account Name</label>
                                <input 
                                    type="text" 
                                    value="<?php echo htmlspecialchars($agent_profile['account_name'] ?? ''); ?>"
                                    readonly
                                    style="background: #F3F4F6;"
                                >
                            </div>
                        </div>

                        <!-- Cash Pickup -->
                        <div class="form-group" id="cashDetails" style="display: none;">
                            <label>Pickup Location Preference</label>
                            <input 
                                type="text" 
                                name="payment_details" 
                                placeholder="e.g., Main Office, Kigali Branch"
                            >
                            <small>Admin will contact you to arrange pickup</small>
                        </div>

                        <div style="padding: 16px; background: #EFF6FF; border-radius: 8px; margin: 24px 0;">
                            <p style="color: #1E40AF; margin: 0;">
                                <strong>⚠️ Note:</strong> Your request will be reviewed by admin. Processing typically takes 1-3 business days.
                            </p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Submit Payout Request
                            </button>
                            <a href="commissions.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        function updatePaymentFields() {
            const method = document.getElementById('paymentMethod').value;
            const momoDetails = document.getElementById('momoDetails');
            const bankDetails = document.getElementById('bankDetails');
            const cashDetails = document.getElementById('cashDetails');
            
            // Hide all
            momoDetails.style.display = 'none';
            bankDetails.style.display = 'none';
            cashDetails.style.display = 'none';
            
            // Show relevant fields
            if (method === 'momo') {
                momoDetails.style.display = 'block';
                momoDetails.querySelector('input').required = true;
            } else if (method === 'bank_transfer') {
                bankDetails.style.display = 'block';
                bankDetails.querySelector('input[name="payment_details"]').required = true;
            } else if (method === 'cash') {
                cashDetails.style.display = 'block';
                cashDetails.querySelector('input').required = true;
            }
        }
    </script>
</body>
</html>
