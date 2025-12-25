<?php
/**
 * Flutterwave Helper Functions
 * All payment processing logic
 */

require_once __DIR__ . '/../config/flutterwave.php';
require_once __DIR__ . '/../config.php';

// =====================================================
// INITIALIZE PAYMENT
// =====================================================
function flw_initialize_payment($user_id, $plan_id, $amount, $currency = 'RWF') {
    global $conn;
    
    try {
        // Check if keys are configured
        if (!flw_keys_configured()) {
            throw new Exception('Flutterwave API keys not configured. Please contact administrator.');
        }
        
        // Get user details
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Get plan details
        $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            throw new Exception('Subscription plan not found');
        }
        
        // Verify amount matches plan
        if (floatval($amount) !== floatval($plan['price'])) {
            throw new Exception('Invalid payment amount');
        }
        
        // Generate unique transaction reference
        $tx_ref = 'TXN-URU-' . time() . '-' . $user_id . '-' . rand(1000, 9999);
        
        // Create transaction record
        $stmt = $conn->prepare("
            INSERT INTO transactions 
            (user_id, transaction_ref, flutterwave_tx_ref, amount, payment_method, status, description, created_at) 
            VALUES (?, ?, ?, ?, 'flutterwave', 'pending', ?, NOW())
        ");
        $description = "Subscription: {$plan['plan_name']}";
        $stmt->execute([
            $user_id,
            $tx_ref,
            $tx_ref,
            $amount,
            $description
        ]);
        $transaction_id = $conn->lastInsertId();
        
        // Prepare payment data for Flutterwave
        $payment_data = [
            'tx_ref' => $tx_ref,
            'amount' => floatval($amount),
            'currency' => $currency,
            'redirect_url' => FLW_REDIRECT_URL . '?tx_ref=' . $tx_ref,
            'payment_options' => FLW_PAYMENT_OPTIONS,
            'customer' => [
                'email' => !empty($user['email']) ? $user['email'] : 'student' . $user_id . '@uruhushya.com',
                'phonenumber' => $user['phone'],
                'name' => $user['full_name']
            ],
            'customizations' => [
                'title' => FLW_BUSINESS_NAME,
                'description' => $description,
                'logo' => FLW_BUSINESS_LOGO
            ],
            'meta' => [
                'user_id' => $user_id,
                'plan_id' => $plan_id,
                'transaction_id' => $transaction_id,
                'plan_name' => $plan['plan_name'],
                'duration_days' => $plan['duration_days']
            ]
        ];
        
        // Log payment request
        flw_log_payment($transaction_id, $user_id, 'request', $payment_data);
        
        return [
            'success' => true,
            'tx_ref' => $tx_ref,
            'transaction_id' => $transaction_id,
            'payment_data' => $payment_data,
            'plan' => $plan
        ];
        
    } catch (Exception $e) {
        error_log("Flutterwave Initialize Error: " . $e->getMessage());
        
        if (isset($transaction_id)) {
            flw_log_payment($transaction_id, $user_id ?? null, 'error', ['error' => $e->getMessage()]);
        }
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// =====================================================
// VERIFY PAYMENT WITH FLUTTERWAVE API
// =====================================================
function flw_verify_payment($transaction_id) {
    try {
        if (!flw_keys_configured()) {
            throw new Exception('Flutterwave API keys not configured');
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => flw_api_url("/transactions/{$transaction_id}/verify"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FLW_SECRET_KEY,
                'Content-Type: application/json'
            ],
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }
        
        $result = json_decode($response, true);
        
        flw_log_payment(null, null, 'verification', $result);
        
        if ($http_code === 200 && isset($result['status']) && $result['status'] === 'success') {
            return [
                'success' => true,
                'data' => $result['data']
            ];
        } else {
            return [
                'success' => false,
                'error' => isset($result['message']) ? $result['message'] : 'Payment verification failed'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Flutterwave Verify Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// =====================================================
// PROCESS SUCCESSFUL PAYMENT
// =====================================================
function flw_process_successful_payment($tx_ref, $flw_transaction_id, $payment_data) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE flutterwave_tx_ref = ? LIMIT 1");
        $stmt->execute([$tx_ref]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception('Transaction not found');
        }
        
        if ($transaction['status'] === 'completed') {
            $conn->rollBack();
            return [
                'success' => true,
                'message' => 'Payment already processed',
                'already_processed' => true
            ];
        }
        
        $paid_amount = floatval($payment_data['amount']);
        $expected_amount = floatval($transaction['amount']);
        
        if ($paid_amount !== $expected_amount) {
            throw new Exception("Amount mismatch: Expected {$expected_amount}, Got {$paid_amount}");
        }
        
        if ($payment_data['currency'] !== FLW_CURRENCY) {
            throw new Exception("Currency mismatch: Expected " . FLW_CURRENCY);
        }
        
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET status = 'completed',
                flutterwave_tx_id = ?,
                payment_response = ?,
                verified_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $flw_transaction_id,
            json_encode($payment_data),
            $transaction['id']
        ]);
        
        $user_id = $transaction['user_id'];
        
        $plan_name = 'Premium Plan';
        $duration_days = 1;
        
        if (isset($payment_data['meta'])) {
            $plan_name = $payment_data['meta']['plan_name'] ?? $plan_name;
            $duration_days = $payment_data['meta']['duration_days'] ?? $duration_days;
        }
        
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$duration_days} days"));
        
        $stmt = $conn->prepare("
            SELECT * FROM subscriptions 
            WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $existing_subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_subscription) {
            $new_end_date = date('Y-m-d', strtotime($existing_subscription['end_date'] . " +{$duration_days} days"));
            
            $stmt = $conn->prepare("
                UPDATE subscriptions 
                SET end_date = ?,
                    amount = amount + ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $new_end_date,
                $transaction['amount'],
                $existing_subscription['id']
            ]);
            
            $end_date = $new_end_date;
        } else {
            $stmt = $conn->prepare("
                INSERT INTO subscriptions 
                (user_id, plan_type, plan_name, amount, currency, start_date, end_date, status, payment_method, tests_limit, created_at) 
                VALUES (?, 'standard', ?, ?, ?, ?, ?, 'active', 'flutterwave', 999, NOW())
            ");
            $stmt->execute([
                $user_id,
                $plan_name,
                $transaction['amount'],
                FLW_CURRENCY,
                $start_date,
                $end_date
            ]);
        }
        
        if (!empty($transaction['agent_id'])) {
            $stmt = $conn->prepare("SELECT commission_rate FROM agent_profile WHERE agent_id = ?");
            $stmt->execute([$transaction['agent_id']]);
            $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
            $commission_rate = $agent_profile['commission_rate'] ?? 10.00;
            
            $commission_amount = ($transaction['amount'] * $commission_rate) / 100;
            
            $stmt = $conn->prepare("
                INSERT INTO agent_sales 
                (agent_id, customer_id, transaction_id, sale_amount, commission_rate, commission_amount, commission_status, sale_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved', CURDATE(), NOW())
            ");
            $stmt->execute([
                $transaction['agent_id'],
                $user_id,
                $transaction['id'],
                $transaction['amount'],
                $commission_rate,
                $commission_amount
            ]);
        }
        
        $conn->commit();
        
        flw_log_payment($transaction['id'], $user_id, 'response', [
            'status' => 'success',
            'message' => 'Payment processed successfully',
            'subscription_end_date' => $end_date
        ]);
        
        return [
            'success' => true,
            'message' => 'Payment processed successfully',
            'subscription_end_date' => $end_date,
            'plan_name' => $plan_name
        ];
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Process Payment Error: " . $e->getMessage());
        
        if (isset($transaction)) {
            flw_log_payment($transaction['id'], $transaction['user_id'], 'error', [
                'error' => $e->getMessage()
            ]);
        }
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// =====================================================
// LOG PAYMENT ACTIVITY
// =====================================================
function flw_log_payment($transaction_id, $user_id, $log_type, $log_data) {
    global $conn;
    
    if (!FLW_ENABLE_LOGGING) {
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO payment_logs 
            (transaction_id, user_id, log_type, log_data, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $transaction_id,
            $user_id,
            $log_type,
            json_encode($log_data),
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Log Payment Error: " . $e->getMessage());
    }
}

// =====================================================
// VERIFY WEBHOOK SIGNATURE
// =====================================================
function flw_verify_webhook_signature($payload, $signature) {
    $expected_signature = hash_hmac('sha256', $payload, FLW_WEBHOOK_SECRET);
    return hash_equals($expected_signature, $signature);
}

// =====================================================
// GET TRANSACTION BY REFERENCE
// =====================================================
function flw_get_transaction_by_ref($tx_ref) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE flutterwave_tx_ref = ? LIMIT 1");
        $stmt->execute([$tx_ref]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get Transaction Error: " . $e->getMessage());
        return false;
    }
}

// =====================================================
// GET PAYMENT STATUS MESSAGE
// =====================================================
function flw_get_status_message($status) {
    $messages = [
        'successful' => 'Payment completed successfully',
        'failed' => 'Payment failed. Please try again.',
        'pending' => 'Payment is being processed',
        'cancelled' => 'Payment was cancelled'
    ];
    
    return $messages[$status] ?? 'Unknown payment status';
}
?>