<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$reason = $_GET['reason'] ?? 'unknown';
$error = $_GET['error'] ?? '';

$error_messages = [
    'cancelled' => 'You cancelled the payment process.',
    'invalid_reference' => 'Invalid payment reference.',
    'verification_failed' => 'Payment verification failed. Please contact support if amount was debited.',
    'unknown' => 'An error occurred during payment processing.'
];

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
    <title>Payment Failed - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
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
    <div class="error-container">
        <div class="error-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        
        <h1 style="font-size: 32px; font-weight: 700; color: #1F2937; margin-bottom: 12px;">Payment Failed</h1>
        <p style="font-size: 18px; color: #6B7280; margin-bottom: 32px;"><?php echo $error_message; ?></p>
        
        <a href="subscription-payment.php" class="btn btn-primary">Try Again</a>
    </div>
</body>
</html>