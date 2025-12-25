<?php
session_start();
require_once '../config.php';
require_once '../config/flutterwave.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
$tx_ref = $_GET['tx_ref'] ?? '';

// Get transaction details
$transaction = null;
$subscription = null;

if (!empty($tx_ref)) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM transactions 
            WHERE flutterwave_tx_ref = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$tx_ref, $user_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            // Get active subscription
            $stmt = $conn->prepare("
                SELECT * FROM subscriptions 
                WHERE user_id = ? AND status = 'active'
                ORDER BY end_date DESC
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Success Page Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .success-title {
            font-size: 32px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
        }
        
        .success-message {
            font-size: 18px;
            color: #6B7280;
            margin-bottom: 32px;
        }
        
        .transaction-details {
            background: #F3F4F6;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6B7280;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1F2937;
            font-weight: 600;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #3B82F6;
            border: 2px solid #3B82F6;
        }
        
        .btn-secondary:hover {
            background: #EFF6FF;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        
        <h1 class="success-title">Payment Successful!</h1>
        <p class="success-message">
            Thank you for your payment. Your subscription is now active!
        </p>
        
        <?php if ($transaction && $subscription): ?>
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Transaction Reference</span>
                    <span class="detail-value"><?php echo htmlspecialchars($transaction['transaction_ref']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount Paid</span>
                    <span class="detail-value"><?php echo number_format($transaction['amount']); ?> RWF</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Plan</span>
                    <span class="detail-value"><?php echo htmlspecialchars($subscription['plan_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Valid Until</span>
                    <span class="detail-value"><?php echo date('F d, Y', strtotime($subscription['end_date'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Date</span>
                    <span class="detail-value"><?php echo date('F d, Y H:i', strtotime($transaction['created_at'])); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 32px;">
            <a href="dashboard.php" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Go to Dashboard
            </a>
            
            <a href="tests.php" class="btn btn-secondary">
                Start Taking Tests
            </a>
        </div>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #E5E7EB;">
            <p style="color: #6B7280; font-size: 14px;">
                A confirmation email has been sent to your registered email address.
            </p>
        </div>
    </div>
</body>
</html>