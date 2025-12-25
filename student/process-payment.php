<?php
session_start();
require_once '../config.php';
require_once '../config/flutterwave.php';
require_once __DIR__ . '/../includes/flutterwave-helper.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get plan ID from request
    $plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
    
    if ($plan_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid plan selected'
        ]);
        exit;
    }
    
    try {
        // Get plan details
        $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            throw new Exception('Plan not found or inactive');
        }
        
        // Initialize payment
        $result = flw_initialize_payment(
            $user_id,
            $plan_id,
            $plan['price'],
            FLW_CURRENCY
        );
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        // Return payment data
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'payment_data' => $result['payment_data'],
            'public_key' => FLW_PUBLIC_KEY,
            'tx_ref' => $result['tx_ref']
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log("Process Payment Error: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    
} else {
    // Invalid request method
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}
?>