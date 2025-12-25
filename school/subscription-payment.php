<?php
session_start();
require_once '../config.php';
require_once '../config/flutterwave.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$school_name = $_SESSION['full_name'];
$error = '';
$success = '';

// Get available school plans
try {
    $stmt = $conn->prepare("
        SELECT * FROM subscription_plans 
        WHERE user_type = 'school' AND is_active = 1 
        ORDER BY price ASC
    ");
    $stmt->execute();
    $school_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no school-specific plans, use student plans
    if (empty($school_plans)) {
        $stmt = $conn->prepare("
            SELECT * FROM subscription_plans 
            WHERE user_type = 'student' AND is_active = 1 
            ORDER BY price ASC
        ");
        $stmt->execute();
        $school_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("School plans error: " . $e->getMessage());
    $school_plans = [];
}

// Handle manual payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_manual'])) {
    $plan_id = (int)$_POST['plan_id'];
    $payment_method = $_POST['payment_method'];
    $payment_phone = trim($_POST['payment_phone'] ?? '');
    $payment_reference = trim($_POST['payment_reference'] ?? '');
    
    if ($plan_id <= 0) {
        $error = 'Please select a subscription plan';
    } elseif (empty($payment_method)) {
        $error = 'Please select a payment method';
    } else {
        try {
            // Get plan details
            $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plan) {
                $error = 'Invalid subscription plan';
            } else {
                // Calculate dates
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));
                
                // Create subscription (pending status)
                $stmt = $conn->prepare("
                    INSERT INTO subscriptions 
                    (user_id, plan_type, plan_name, amount, currency, start_date, end_date, status, payment_method, tests_limit, created_at) 
                    VALUES (?, ?, ?, ?, 'RWF', ?, ?, 'pending', ?, ?, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $plan['duration_type'],
                    $plan['plan_name'],
                    $plan['price'],
                    $start_date,
                    $end_date,
                    $payment_method,
                    $plan['tests_limit']
                ]);
                $subscription_id = $conn->lastInsertId();
                
                // Generate transaction reference
                $transaction_ref = 'TXN-SCH-' . time() . '-' . rand(1000, 9999);
                
                // Create transaction
                $stmt = $conn->prepare("
                    INSERT INTO transactions 
                    (user_id, subscription_id, transaction_ref, amount, payment_method, payment_phone, status, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $subscription_id,
                    $transaction_ref,
                    $plan['price'],
                    $payment_method,
                    $payment_phone,
                    "Subscription: {$plan['plan_name']} (Manual Payment)"
                ]);
                
                $success = 'Subscription request submitted successfully! Awaiting admin approval.';
            }
        } catch (Exception $e) {
            error_log("Manual subscription error: " . $e->getMessage());
            $error = 'Failed to submit subscription. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Payment - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
    <style>
        .payment-methods-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .payment-option {
            background: white;
            border: 3px solid #E5E7EB;
            border-radius: 16px;
            padding: 32px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: #3B82F6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            transform: translateY(-4px);
        }
        
        .payment-option.selected {
            border-color: #3B82F6;
            background: #EFF6FF;
        }
        
        .payment-option-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .payment-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .payment-option h3 {
            font-size: 24px;
            color: #1F2937;
            margin: 0;
        }
        
        .payment-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #10B981;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .payment-features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .payment-features li {
            padding: 8px 0;
            color: #6B7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-features li::before {
            content: "✓";
            color: #10B981;
            font-weight: 700;
        }
        
        .divider {
            text-align: center;
            margin: 40px 0;
            position: relative;
        }
        
        .divider::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 2px;
            background: #E5E7EB;
        }
        
        .divider span {
            background: white;
            padding: 0 20px;
            position: relative;
            color: #9CA3AF;
            font-weight: 600;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        
        .plan-card {
            background: white;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s;
        }
        
        .plan-card:hover {
            border-color: #3B82F6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        
        .plan-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .plan-name {
            font-size: 20px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        .plan-price {
            font-size: 36px;
            font-weight: 700;
            color: #3B82F6;
        }
        
        .plan-currency {
            font-size: 16px;
            color: #6B7280;
        }
        
        .plan-duration {
            color: #6B7280;
            margin-top: 4px;
        }
        
        .btn-pay {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 16px;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .manual-payment-form {
            display: none;
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-top: 32px;
        }
        
        .manual-payment-form.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Sidebar (same as school dashboard) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🏫 URUHUSHYA</h2>
            <p>School Portal</p>
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
            
            <a href="subscription-payment.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Subscription
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="btn-logout">
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
            <h1>Subscription Payment</h1>
            <div class="admin-info">
                <span>🏫 <strong><?php echo htmlspecialchars($school_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            <div class="payment-methods-container">
                
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
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <h2 style="text-align: center; margin-bottom: 32px;">Choose Payment Method</h2>

                <!-- Payment Method Options -->
                <div class="payment-options">
                    <!-- Flutterwave Payment -->
                    <div class="payment-option" onclick="selectPaymentMethod('flutterwave')">
                        <div class="payment-option-header">
                            <div class="payment-icon">💳</div>
                            <div>
                                <h3>Flutterwave Payment</h3>
                                <span class="payment-badge">INSTANT ACTIVATION</span>
                            </div>
                        </div>
                        <ul class="payment-features">
                            <li>Pay with Card (Visa, Mastercard)</li>
                            <li>Mobile Money (MTN, Airtel)</li>
                            <li>Bank Transfer</li>
                            <li>Instant subscription activation</li>
                            <li>Secure payment processing</li>
                        </ul>
                    </div>

                    <!-- Manual Payment -->
                    <div class="payment-option" onclick="selectPaymentMethod('manual')">
                        <div class="payment-option-header">
                            <div class="payment-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">🏦</div>
                            <div>
                                <h3>Manual Payment</h3>
                                <span class="payment-badge" style="background: #F59E0B;">ADMIN APPROVAL</span>
                            </div>
                        </div>
                        <ul class="payment-features">
                            <li>Bank Transfer</li>
                            <li>Cash Payment</li>
                            <li>Payment on delivery</li>
                            <li>Requires admin verification</li>
                            <li>Activation within 24 hours</li>
                        </ul>
                    </div>
                </div>

                <div class="divider">
                    <span>SELECT A PLAN</span>
                </div>

                <!-- Flutterwave Plans (Initially Hidden) -->
                <div id="flutterwavePlans" style="display: none;">
                    <h3 style="text-align: center; margin-bottom: 24px;">Pay Instantly with Flutterwave</h3>
                    <div class="plans-grid">
                        <?php foreach ($school_plans as $plan): ?>
                            <div class="plan-card">
                                <div class="plan-header">
                                    <div class="plan-name"><?php echo htmlspecialchars($plan['plan_name']); ?></div>
                                    <div>
                                        <span class="plan-price"><?php echo number_format($plan['price']); ?></span>
                                        <span class="plan-currency">RWF</span>
                                    </div>
                                    <div class="plan-duration">For <?php echo $plan['duration_days']; ?> days</div>
                                </div>
                                
                                <button class="btn-pay" onclick="initiateSchoolPayment(<?php echo $plan['id']; ?>, <?php echo $plan['price']; ?>, '<?php echo htmlspecialchars($plan['plan_name']); ?>')">
                                    Pay Now with Flutterwave
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Manual Payment Form (Initially Hidden) -->
                <div id="manualPaymentForm" class="manual-payment-form">
                    <h3 style="margin-bottom: 24px;">Manual Payment Details</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Select Plan *</label>
                            <select name="plan_id" required>
                                <option value="">-- Select a plan --</option>
                                <?php foreach ($school_plans as $plan): ?>
                                    <option value="<?php echo $plan['id']; ?>">
                                        <?php echo htmlspecialchars($plan['plan_name']); ?> - 
                                        <?php echo number_format($plan['price']); ?> RWF
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" id="manualPaymentMethod" required onchange="toggleManualPhoneField()">
                                <option value="">-- Select payment method --</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="momo">Mobile Money</option>
                                <option value="cash">Cash Payment</option>
                            </select>
                        </div>

                        <div class="form-group" id="manualPhoneField" style="display: none;">
                            <label>Mobile Money Number</label>
                            <input type="tel" name="payment_phone" placeholder="078XXXXXXX" pattern="[0-9]{10}">
                        </div>

                        <div class="form-group">
                            <label>Payment Reference (Optional)</label>
                            <input type="text" name="payment_reference" placeholder="Transaction ID or reference number">
                        </div>

                        <div style="padding: 16px; background: #FEF3C7; border-radius: 8px; margin: 24px 0;">
                            <p style="color: #92400E; margin: 0;">
                                <strong>⚠️ Note:</strong> Your subscription will be activated after admin verification (usually within 24 hours).
                            </p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit_manual" class="btn btn-primary">
                                Submit Payment Request
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetPaymentMethod()">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Flutterwave Scripts -->
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script>
        function selectPaymentMethod(method) {
            // Reset all options
            document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
            document.getElementById('flutterwavePlans').style.display = 'none';
            document.getElementById('manualPaymentForm').style.display = 'none';
            
            // Select clicked option
            event.currentTarget.classList.add('selected');
            
            // Show corresponding section
            if (method === 'flutterwave') {
                document.getElementById('flutterwavePlans').style.display = 'block';
            } else if (method === 'manual') {
                document.getElementById('manualPaymentForm').style.display = 'block';
            }
            
            // Scroll to plans
            setTimeout(() => {
                window.scrollTo({
                    top: document.querySelector('.divider').offsetTop - 100,
                    behavior: 'smooth'
                });
            }, 100);
        }
        
        function resetPaymentMethod() {
            document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
            document.getElementById('flutterwavePlans').style.display = 'none';
            document.getElementById('manualPaymentForm').style.display = 'none';
        }
        
        function toggleManualPhoneField() {
            const method = document.getElementById('manualPaymentMethod').value;
            const phoneField = document.getElementById('manualPhoneField');
            
            if (method === 'momo') {
                phoneField.style.display = 'block';
                phoneField.querySelector('input').required = true;
            } else {
                phoneField.style.display = 'none';
                phoneField.querySelector('input').required = false;
            }
        }
        
        function initiateSchoolPayment(planId, planPrice, planName) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '⏳ Processing...';
            
            fetch('process-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'plan_id=' + planId
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to initialize payment');
                }
                
                FlutterwaveCheckout({
                    public_key: data.public_key,
                    tx_ref: data.payment_data.tx_ref,
                    amount: data.payment_data.amount,
                    currency: data.payment_data.currency,
                    payment_options: data.payment_data.payment_options,
                    customer: data.payment_data.customer,
                    customizations: data.payment_data.customizations,
                    meta: data.payment_data.meta,
                    callback: function(paymentData) {
                        console.log('✅ Payment callback:', paymentData);
                        window.location.href = 'payment-callback.php?status=' + paymentData.status + 
                                               '&tx_ref=' + paymentData.tx_ref + 
                                               '&transaction_id=' + paymentData.transaction_id;
                    },
                    onclose: function() {
                        console.log('Payment modal closed');
                    }
                });
                
                button.disabled = false;
                button.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Payment Error:', error);
                alert('❌ Error: ' + error.message);
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
    </script>
</body>
</html>