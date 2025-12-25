<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$school_name = $_SESSION['full_name'];
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
        error_log("School Success Page Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
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
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .success-title {
            font-size: 32px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
        }
        
        .transaction-details {
            background: #F3F4F6;
            padding: 24px;
            border-radius: 12px;
            margin: 32px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .detail-row:last-child { border-bottom: none; }
        
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
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
        <p style="color: #6B7280; font-size: 18px; margin-bottom: 32px;">
            Your school subscription is now active!
        </p>
        
        <?php if ($transaction && $subscription): ?>
            <div class="transaction-details">
                <div class="detail-row">
                    <span style="color: #6B7280;">Transaction Reference</span>
                    <span style="font-weight: 600;"><?php echo htmlspecialchars($transaction['transaction_ref']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span style="color: #6B7280;">Amount Paid</span>
                    <span style="font-weight: 600;"><?php echo number_format($transaction['amount']); ?> RWF</span>
                </div>
                
                <div class="detail-row">
                    <span style="color: #6B7280;">Plan</span>
                    <span style="font-weight: 600;"><?php echo htmlspecialchars($subscription['plan_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span style="color: #6B7280;">Valid Until</span>
                    <span style="font-weight: 600;"><?php echo date('F d, Y', strtotime($subscription['end_date'])); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    </div>
</body>
</html>