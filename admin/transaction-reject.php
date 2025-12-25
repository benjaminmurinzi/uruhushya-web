<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transactions.php');
    exit;
}

$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;

if ($transaction_id <= 0) {
    header('Location: transactions.php?error=invalid_transaction');
    exit;
}

try {
    // Get transaction details
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        header('Location: transactions.php?error=transaction_not_found');
        exit;
    }
    
    // Update transaction status
    $stmt = $conn->prepare("
        UPDATE transactions 
        SET status = 'failed', 
            processed_by = ?, 
            processed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $transaction_id]);
    
    // If transaction has a subscription, cancel it
    if ($transaction['subscription_id']) {
        $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$transaction['subscription_id']]);
    }
    
    // TODO: Send notification to user about rejection
    
    header('Location: transactions.php?success=rejected');
    exit;
    
} catch (Exception $e) {
    error_log("Reject transaction error: " . $e->getMessage());
    header('Location: transactions.php?error=reject_failed');
    exit;
}
?>