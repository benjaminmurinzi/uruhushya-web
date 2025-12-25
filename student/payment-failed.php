<?php
session_start();
require_once '../config.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$reason = $_GET['reason'] ?? 'unknown';
$error = $_GET['error'] ?? '';

// Determine error message
$error_messages = [
    'cancelled' => 'You cancelled the payment process.',
    'invalid_reference' => 'Invalid payment reference.',
    'verification_failed' => 'Payment verification failed. Please contact support if amount was debited.',
    'unknown' => 'An error occurred during payment processing.'
];

$error_title = 'Payment Failed';
$error_message = $error_messages[$reason] ?? $error_messages['unknown'];

if (!empty($error)) {
    $error_message .= ' Error: ' . htmlspecialchars($error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .error-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
        }
        
        .error-title {
            font-size: 32px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
        }
        
        .error-message {
            font-size: 18px;
            color: #6B7280;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .info-box {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 16px;
            margin-bottom: 32px;
            text-align: left;
            border-radius: 8px;
        }
        
        .info-box p {
            color: #92400E;
            margin: 0;
            font-size: 14px;
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
            color: #6B7280;
            border: 2px solid #E5E7EB;
        }
        
        .btn-secondary:hover {
            background: #F9FAFB;
            border-color: #D1D5DB;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        
        <h1 class="error-title"><?php echo $error_title; ?></h1>
        <p class="error-message"><?php echo $error_message; ?></p>
        
        <?php if ($reason === 'verification_failed'): ?>
            <div class="info-box">
                <p>
                    <strong>⚠️ Important:</strong> If money was deducted from your account, please contact our support team with your transaction reference. We'll verify and activate your subscription manually.
                </p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 32px;">
            <a href="dashboard.php" class="btn btn-primary">
                Try Again
            </a>
            
            <a href="dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
        
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #E5E7EB;">
            <p style="color: #6B7280; font-size: 14px; margin-bottom: 8px;">
                Need help?
            </p>
            <p style="color: #3B82F6; font-size: 14px;">
                📧 support@uruhushya.com | 📞 +250 788 000 000
            </p>
        </div>
    </div>
</body>
</html>