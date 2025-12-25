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

// Get current settings
$settings = [];
$stmt = $conn->query("SELECT * FROM payment_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update payment settings
        $updates = [
            'momo_api_key' => $_POST['momo_api_key'] ?? '',
            'momo_api_secret' => $_POST['momo_api_secret'] ?? '',
            'momo_enabled' => isset($_POST['momo_enabled']) ? '1' : '0',
            'bank_account_number' => $_POST['bank_account_number'] ?? '',
            'bank_account_name' => $_POST['bank_account_name'] ?? '',
            'bank_name' => $_POST['bank_name'] ?? '',
            'cash_payment_enabled' => isset($_POST['cash_payment_enabled']) ? '1' : '0',
            'payment_instructions' => $_POST['payment_instructions'] ?? ''
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO payment_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$key, $value, $value]);
        }
        
        $success = 'Settings updated successfully!';
        
        // Refresh settings
        $settings = [];
        $stmt = $conn->query("SELECT * FROM payment_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (Exception $e) {
        error_log("Settings update error: " . $e->getMessage());
        $error = 'Failed to update settings. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
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
            
            <a href="tests.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Tests
            </a>
            
            <a href="questions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Questions
            </a>
            
            <a href="subscriptions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Subscriptions
            </a>
            
            <a href="transactions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Transactions
            </a>
            
            <a href="requests.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Requests
            </a>
            
            <a href="analytics.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Analytics
            </a>
            
            <a href="settings.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                </svg>
                Settings
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
            <h1>System Settings</h1>
            <div class="admin-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
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

            <div class="form-container">
                <form method="POST">
                    
                    <!-- Mobile Money Settings -->
                    <div class="settings-section">
                        <h2>Mobile Money (MoMo) Settings</h2>
                        <p style="color: #6B7280; margin-bottom: 20px;">Configure MTN Mobile Money API integration</p>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="momo_enabled" <?php echo ($settings['momo_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                Enable Mobile Money Payments
                            </label>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>MoMo API Key</label>
                                <input 
                                    type="text" 
                                    name="momo_api_key" 
                                    value="<?php echo htmlspecialchars($settings['momo_api_key'] ?? ''); ?>"
                                    placeholder="Enter MTN MoMo API Key"
                                >
                            </div>

                            <div class="form-group">
                                <label>MoMo API Secret</label>
                                <input 
                                    type="password" 
                                    name="momo_api_secret" 
                                    value="<?php echo htmlspecialchars($settings['momo_api_secret'] ?? ''); ?>"
                                    placeholder="Enter MTN MoMo API Secret"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer Settings -->
                    <div class="settings-section">
                        <h2>Bank Transfer Settings</h2>
                        <p style="color: #6B7280; margin-bottom: 20px;">Bank account information for manual transfers</p>
                        
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input 
                                type="text" 
                                name="bank_name" 
                                value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>"
                                placeholder="e.g., Bank of Kigali"
                            >
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Account Number</label>
                                <input 
                                    type="text" 
                                    name="bank_account_number" 
                                    value="<?php echo htmlspecialchars($settings['bank_account_number'] ?? ''); ?>"
                                    placeholder="Enter bank account number"
                                >
                            </div>

                            <div class="form-group">
                                <label>Account Name</label>
                                <input 
                                    type="text" 
                                    name="bank_account_name" 
                                    value="<?php echo htmlspecialchars($settings['bank_account_name'] ?? ''); ?>"
                                    placeholder="Account holder name"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Cash Payment Settings -->
                    <div class="settings-section">
                        <h2>Cash Payment Settings</h2>
                        <p style="color: #6B7280; margin-bottom: 20px;">Enable offline cash payments</p>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="cash_payment_enabled" <?php echo ($settings['cash_payment_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Accept Cash Payments (Offline)
                            </label>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="settings-section">
                        <h2>Payment Instructions</h2>
                        <p style="color: #6B7280; margin-bottom: 20px;">Instructions shown to users during payment</p>
                        
                        <div class="form-group">
                            <label>General Payment Instructions</label>
                            <textarea 
                                name="payment_instructions" 
                                rows="6"
                                placeholder="Enter payment instructions for users..."
                            ><?php echo htmlspecialchars($settings['payment_instructions'] ?? ''); ?></textarea>
                            <small>This will be displayed on the payment page</small>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="settings-section" style="background: #F9FAFB; padding: 24px; border-radius: 12px;">
                        <h2>System Information</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>PHP Version</label>
                                <p><?php echo phpversion(); ?></p>
                            </div>
                            
                            <div class="detail-item">
                                <label>Database</label>
                                <p>MySQL <?php echo $conn->getAttribute(PDO::ATTR_SERVER_VERSION); ?></p>
                            </div>
                            
                            <div class="detail-item">
                                <label>Server Software</label>
                                <p><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                            </div>
                            
                            <div class="detail-item">
                                <label>Site URL</label>
                                <p><?php echo SITE_URL; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <style>
        .settings-section {
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .settings-section:last-of-type {
            border-bottom: none;
        }
        
        .settings-section h2 {
            font-size: 20px;
            margin-bottom: 8px;
            color: #1F2937;
        }
        
        .form-group label input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }
    </style>
</body>
</html>