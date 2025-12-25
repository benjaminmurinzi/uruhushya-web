<?php
session_start();
require_once '../config.php';
require_once '../config/flutterwave.php';
require_once '../includes/flutterwave-helper.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get parameters from Flutterwave
$status = $_GET['status'] ?? '';
$tx_ref = $_GET['tx_ref'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';

// Log callback
error_log("Payment Callback - Status: {$status}, TX Ref: {$tx_ref}, Transaction ID: {$transaction_id}");

// Check if payment was cancelled
if ($status === 'cancelled') {
    header('Location: payment-failed.php?reason=cancelled');
    exit;
}

// Verify transaction reference exists
if (empty($tx_ref) || empty($transaction_id)) {
    header('Location: payment-failed.php?reason=invalid_reference');
    exit;
}

try {
    // Verify payment with Flutterwave
    $verification = flw_verify_payment($transaction_id);
    
    if (!$verification['success']) {
        throw new Exception($verification['error']);
    }
    
    $payment_data = $verification['data'];
    
    // Check payment status
    if ($payment_data['status'] !== 'successful') {
        throw new Exception('Payment not successful: ' . $payment_data['status']);
    }
    
    // Verify transaction reference matches
    if ($payment_data['tx_ref'] !== $tx_ref) {
        throw new Exception('Transaction reference mismatch');
    }
    
    // Process the payment
    $result = flw_process_successful_payment(
        $tx_ref,
        $transaction_id,
        $payment_data
    );
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // Payment successful - redirect to success page
    header('Location: payment-success.php?tx_ref=' . urlencode($tx_ref));
    exit;
    
} catch (Exception $e) {
    error_log("Payment Callback Error: " . $e->getMessage());
    
    // Redirect to failed page with error
    header('Location: payment-failed.php?reason=verification_failed&error=' . urlencode($e->getMessage()));
    exit;
}
?>