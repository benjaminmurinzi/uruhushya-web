<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$error = '';
$success = '';

// Get all users (students, schools, agents)
$stmt = $conn->query("SELECT id, full_name, email, user_type FROM users WHERE status = 'active' ORDER BY full_name ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subscription plans
$stmt = $conn->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $plan_id = (int)$_POST['plan_id'];
    $payment_method = $_POST['payment_method'];
    $amount = (float)$_POST['amount'];
    $start_date = $_POST['start_date'];
    $notes = trim($_POST['notes']);
    
    // Validation
    if ($user_id <= 0) {
        $error = 'Please select a user';
    } elseif ($plan_id <= 0) {
        $error = 'Please select a subscription plan';
    } elseif ($amount <= 0) {
        $error = 'Amount must be greater than 0';
    } elseif (empty($start_date)) {
        $error = 'Please select start date';
    } else {
        try {
            // Get plan details
            $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plan) {
                $error = 'Invalid subscription plan';
            } else {
                // Calculate end date
                $start = new DateTime($start_date);
                $end = clone $start;
                $end->modify('+' . $plan['duration_days'] . ' days');
                $end_date = $end->format('Y-m-d');
                
                // Create subscription
                $stmt = $conn->prepare("
                    INSERT INTO subscriptions 
                    (user_id, plan_type, plan_name, amount, start_date, end_date, status, payment_method, tests_limit, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $plan['duration_type'],
                    $plan['plan_name'],
                    $amount,
                    $start_date,
                    $end_date,
                    $payment_method,
                    $plan['tests_limit'],
                    $notes
                ]);
                
                $subscription_id = $conn->lastInsertId();
                
                // Create transaction record
                $transaction_ref = 'TXN-MANUAL-' . time() . '-' . rand(1000, 9999);
                $stmt = $conn->prepare("
                    INSERT INTO transactions 
                    (user_id, subscription_id, transaction_ref, amount, payment_method, status, description, processed_by, processed_at) 
                    VALUES (?, ?, ?, ?, ?, 'completed', 'Manual subscription created by admin', ?, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $subscription_id,
                    $transaction_ref,
                    $amount,
                    $payment_method,
                    $_SESSION['user_id']
                ]);
                
                header('Location: subscriptions.php?success=created');
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Create subscription error: " . $e->getMessage());
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
    <title>Create Subscription - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as subscriptions.php) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>URUHUSHYA</h2>
            <p>Admin Panel</p>
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
            
            <a href="users.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Users
            </a>
            
            <a href="subscriptions.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Subscriptions
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
            <h1>Create Manual Subscription</h1>
            <div class="admin-info">
                <a href="subscriptions.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Subscriptions
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="form-container">
                <h2>Create Subscription for Offline Payment</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Use this form to manually create subscriptions for users who paid via cash, bank transfer, or other offline methods.
                </p>
                
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

                <form method="POST" id="subscriptionForm">
                    
                    <div class="form-group">
                        <label>Select User *</label>
                        <select name="user_id" id="userId" required>
                            <option value="">-- Select User --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" data-type="<?php echo $user['user_type']; ?>">
                                    <?php echo htmlspecialchars($user['full_name']); ?> 
                                    (<?php echo htmlspecialchars($user['email']); ?>) 
                                    - <?php echo ucfirst($user['user_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Plan *</label>
                        <select name="plan_id" id="planId" required>
                            <option value="">-- Select Plan --</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>" 
                                        data-price="<?php echo $plan['price']; ?>"
                                        data-duration="<?php echo $plan['duration_days']; ?>"
                                        data-type="<?php echo $plan['user_type']; ?>">
                                    <?php echo htmlspecialchars($plan['plan_name']); ?> 
                                    (<?php echo number_format($plan['price']); ?> RWF - <?php echo ucfirst($plan['duration_type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Plans are filtered based on selected user type</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Amount (RWF) *</label>
                            <input 
                                type="number" 
                                name="amount" 
                                id="amount"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                            >
                            <small>Amount can be adjusted for discounts</small>
                        </div>

                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="momo">Mobile Money</option>
                                <option value="card">Card Payment</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Start Date *</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            value="<?php echo date('Y-m-d'); ?>"
                            required
                        >
                        <small>End date will be calculated automatically based on plan duration</small>
                    </div>

                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea 
                            name="notes" 
                            rows="4"
                            placeholder="Add any internal notes about this subscription..."
                        ></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Create Subscription
                        </button>
                        <a href="subscriptions.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        // Filter plans based on user type
        document.getElementById('userId').addEventListener('change', function() {
            const userType = this.options[this.selectedIndex].dataset.type;
            const planSelect = document.getElementById('planId');
            const options = planSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') return;
                
                const planType = option.dataset.type;
                if (planType === userType || planType === 'all') {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        planSelect.value = '';
                        document.getElementById('amount').value = '';
                    }
                }
            });
        });

        // Auto-fill amount when plan is selected
        document.getElementById('planId').addEventListener('change', function() {
            const price = this.options[this.selectedIndex].dataset.price;
            if (price) {
                document.getElementById('amount').value = price;
            }
        });
    </script>
</body>
</html>