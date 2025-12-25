<?php
session_start();
require_once '../config.php';

// Check if agent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$agent_id = $_SESSION['user_id'];
$agent_name = $_SESSION['full_name'];
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$error = '';
$success = $_GET['success'] ?? '';

if ($client_id <= 0) {
    header('Location: clients.php?error=invalid_client');
    exit;
}

// Get client details
try {
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE id = ? AND referred_by = ?
    ");
    $stmt->execute([$client_id, $agent_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        header('Location: clients.php?error=client_not_found');
        exit;
    }
    
    // Get available plans based on client type
    $stmt = $conn->prepare("
        SELECT * FROM subscription_plans 
        WHERE user_type = ? AND is_active = 1 
        ORDER BY price ASC
    ");
    $stmt->execute([$client['user_type']]);
    $available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get agent commission rate
    $stmt = $conn->prepare("SELECT commission_rate FROM agent_profile WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    $commission_rate = $agent_profile['commission_rate'] ?? 10.00;
    
} catch (Exception $e) {
    error_log("Create subscription error: " . $e->getMessage());
    $error = 'Failed to load client data';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = (int)$_POST['plan_id'];
    $payment_method = $_POST['payment_method'];
    $payment_phone = trim($_POST['payment_phone'] ?? '');
    
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
                
                // Create subscription
                $stmt = $conn->prepare("
                    INSERT INTO subscriptions 
                    (user_id, agent_id, plan_type, plan_name, amount, currency, start_date, end_date, status, payment_method, tests_limit, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'RWF', ?, ?, 'pending', ?, ?, NOW())
                ");
                $stmt->execute([
                    $client_id,
                    $agent_id,
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
                $transaction_ref = 'TXN-AGT-' . time() . '-' . rand(1000, 9999);
                
                // Create transaction with agent tracking
                $stmt = $conn->prepare("
                    INSERT INTO transactions 
                    (user_id, agent_id, subscription_id, transaction_ref, amount, payment_method, payment_phone, status, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([
                    $client_id,
                    $agent_id,
                    $subscription_id,
                    $transaction_ref,
                    $plan['price'],
                    $payment_method,
                    $payment_phone,
                    "Subscription: {$plan['plan_name']} (via Agent)"
                ]);
                
                // Redirect to success page
                header("Location: clients.php?success=subscription_created");
                exit;
            }
        } catch (Exception $e) {
            error_log("Subscription creation error: " . $e->getMessage());
            $error = 'Failed to create subscription. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Subscription - URUHUSHYA Agent</title>
    <link rel="stylesheet" href="../assets/css/agent-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as register-client.php) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>💼 URUHUSHYA</h2>
            <p>Agent Portal</p>
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
            
            <a href="clients.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Clients
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">
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
            <h1>Create Subscription</h1>
            <div class="admin-info">
                <a href="clients.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Clients
                </a>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'client_registered'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Client registered successfully! Now create their subscription.
                </div>
            <?php endif; ?>
            
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

            <!-- Client Info Card -->
            <div class="user-profile-card" style="margin-bottom: 32px;">
                <h3 style="margin-bottom: 16px;">Client Information</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Name</label>
                        <p><strong><?php echo htmlspecialchars($client['full_name']); ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Type</label>
                        <p><?php echo ucfirst($client['user_type']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Phone</label>
                        <p><?php echo htmlspecialchars($client['phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Your Commission Rate</label>
                        <p><strong style="color: #6366F1;"><?php echo $commission_rate; ?>%</strong></p>
                    </div>
                </div>
            </div>

            <!-- Subscription Form -->
            <div class="form-container">
                <h2>Select Subscription Plan</h2>
                
                <?php if (empty($available_plans)): ?>
                    <div class="alert alert-error">
                        No subscription plans available for this client type. Please contact admin.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        
                        <!-- Plan Selection -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 32px;">
                            <?php foreach ($available_plans as $plan): ?>
                                <?php 
                                $commission = ($plan['price'] * $commission_rate / 100);
                                ?>
                                <label class="plan-card <?php echo isset($_POST['plan_id']) && $_POST['plan_id'] == $plan['id'] ? 'selected' : ''; ?>">
                                    <input type="radio" name="plan_id" value="<?php echo $plan['id']; ?>" required style="display: none;">
                                    <div class="plan-header">
                                        <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                        <div class="price">
                                            <span class="amount"><?php echo number_format($plan['price']); ?></span>
                                            <span class="currency">RWF</span>
                                        </div>
                                        <p class="period"><?php echo ucfirst($plan['duration_type']); ?></p>
                                    </div>
                                    
                                    <div class="commission-badge">
                                        Your Commission: <strong><?php echo number_format($commission); ?> RWF</strong>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Payment Method -->
                        <h3 style="margin-bottom: 16px; color: #1F2937;">Payment Method</h3>
                        
                        <div class="form-group">
                            <select name="payment_method" id="paymentMethod" required onchange="togglePhoneField()">
                                <option value="">-- Select payment method --</option>
                                <option value="momo">Mobile Money (MTN/Airtel)</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash Payment</option>
                            </select>
                        </div>

                        <div class="form-group" id="phoneField" style="display: none;">
                            <label>Mobile Money Number</label>
                            <input 
                                type="tel" 
                                name="payment_phone" 
                                placeholder="078XXXXXXX"
                                pattern="[0-9]{10}"
                            >
                        </div>

                        <div style="padding: 16px; background: #FEF3C7; border-radius: 8px; margin: 24px 0;">
                            <p style="color: #92400E; margin: 0;">
                                <strong>⚠️ Important:</strong> Once payment is confirmed by admin, your commission will be automatically calculated and added to your account.
                            </p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Create Subscription
                            </button>
                            <a href="clients.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        function togglePhoneField() {
            const paymentMethod = document.getElementById('paymentMethod').value;
            const phoneField = document.getElementById('phoneField');
            
            if (paymentMethod === 'momo') {
                phoneField.style.display = 'block';
                phoneField.querySelector('input').required = true;
            } else {
                phoneField.style.display = 'none';
                phoneField.querySelector('input').required = false;
            }
        }
        
        // Plan card selection styling
        document.querySelectorAll('.plan-card input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.plan-card').forEach(card => {
                    card.classList.remove('selected');
                });
                this.closest('.plan-card').classList.add('selected');
            });
        });
    </script>

    <style>
        .plan-card {
            background: white;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .plan-card:hover {
            border-color: #6366F1;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.1);
        }
        
        .plan-card.selected {
            border-color: #6366F1;
            background: #EFF6FF;
        }
        
        .plan-header h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #1F2937;
        }
        
        .price .amount {
            font-size: 36px;
            font-weight: 700;
            color: #6366F1;
        }
        
        .price .currency {
            font-size: 16px;
            color: #6B7280;
        }
        
        .period {
            color: #6B7280;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .commission-badge {
            margin-top: 16px;
            padding: 12px;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
        }
        
        .commission-badge strong {
            font-size: 18px;
        }
    </style>
</body>
</html>