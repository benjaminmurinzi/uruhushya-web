<?php
session_start();
require_once '../config.php';
require_once '../config/flutterwave.php';
require_once __DIR__ . '/../config/flutterwave-helper.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
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
        // Get plan details (try school plans first, then student plans)
        $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            throw new Exception('Plan not found or inactive');
        }
        
        // Initialize payment (reuse student function)
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
        error_log("School Process Payment Error: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}
?>