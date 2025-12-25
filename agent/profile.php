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
$error = '';
$success = '';

// Get agent details
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent) {
        header('Location: logout.php');
        exit;
    }
    
    // Get agent profile
    $stmt = $conn->prepare("SELECT * FROM agent_profile WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no profile exists, create one
    if (!$agent_profile) {
        $stmt = $conn->prepare("INSERT INTO agent_profile (agent_id, commission_rate) VALUES (?, 10.00)");
        $stmt->execute([$agent_id]);
        
        $stmt = $conn->prepare("SELECT * FROM agent_profile WHERE agent_id = ?");
        $stmt->execute([$agent_id]);
        $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE referred_by = ?");
    $stmt->execute([$agent_id]);
    $total_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->prepare("
        SELECT SUM(commission_amount) as total 
        FROM agent_sales 
        WHERE agent_id = ?
    ");
    $stmt->execute([$agent_id]);
    $total_earned = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = 'Failed to load profile data';
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($full_name) || empty($phone)) {
        $error = 'Full name and phone number are required';
    } else {
        try {
            // Check if phone already exists (excluding current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $agent_id]);
            if ($stmt->fetch()) {
                $error = 'Phone number already in use';
            } else {
                // Check email if provided
                if (!empty($email)) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $agent_id]);
                    if ($stmt->fetch()) {
                        $error = 'Email already in use';
                    }
                }
                
                if (!$error) {
                    // Update profile
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, phone = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$full_name, $email, $phone, $agent_id]);
                    
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['phone'] = $phone;
                    
                    $success = 'Profile updated successfully';
                    
                    // Refresh agent data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$agent_id]);
                    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
                    $agent_name = $full_name;
                }
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle payment details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $bank_name = trim($_POST['bank_name']);
    $account_number = trim($_POST['account_number']);
    $account_name = trim($_POST['account_name']);
    $momo_number = trim($_POST['momo_number']);
    $id_number = trim($_POST['id_number']);
    $address = trim($_POST['address']);
    
    try {
        // Update agent profile
        $stmt = $conn->prepare("
            UPDATE agent_profile 
            SET bank_name = ?, account_number = ?, account_name = ?, 
                momo_number = ?, id_number = ?, address = ?, updated_at = NOW()
            WHERE agent_id = ?
        ");
        $stmt->execute([
            $bank_name, $account_number, $account_name, 
            $momo_number, $id_number, $address, $agent_id
        ]);
        
        $success = 'Payment details updated successfully';
        
        // Refresh agent profile
        $stmt = $conn->prepare("SELECT * FROM agent_profile WHERE agent_id = ?");
        $stmt->execute([$agent_id]);
        $agent_profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Payment details update error: " . $e->getMessage());
        $error = 'Failed to update payment details. Please try again.';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } else {
        try {
            // Verify current password
            if (!password_verify($current_password, $agent['password'])) {
                $error = 'Current password is incorrect';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $agent_id]);
                
                $success = 'Password changed successfully';
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = 'Failed to change password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings - URUHUSHYA Agent</title>
    <link rel="stylesheet" href="../assets/css/agent-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
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
            
            <a href="clients.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Clients
            </a>
            
            <a href="sales.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Sales
            </a>
            
            <a href="commissions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Commissions
            </a>
            
            <a href="profile.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Profile
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
            <h1>Profile & Settings</h1>
            <div class="admin-info">
                <span>💼 <strong><?php echo htmlspecialchars($agent_name); ?></strong></span>
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

            <!-- Profile Overview -->
            <div class="user-profile-card" style="margin-bottom: 32px;">
                <div class="profile-header">
                    <div class="profile-avatar" style="width: 100px; height: 100px; font-size: 40px; background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                        <?php 
                        $initials = strtoupper(substr($agent['full_name'], 0, 2));
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($agent['full_name']); ?></h2>
                        <p class="user-type">
                            <span class="status-badge active">Sales Agent</span>
                        </p>
                        <p style="color: #6B7280; margin-top: 8px;">
                            Member since: <?php echo date('F Y', strtotime($agent['created_at'])); ?>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #6366F1;">
                                <?php echo $agent_profile['commission_rate']; ?>%
                            </div>
                            <div style="color: #6B7280; font-size: 14px;">Commission Rate</div>
                        </div>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <label>Full Name</label>
                        <p><strong><?php echo htmlspecialchars($agent['full_name']); ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($agent['phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($agent['email'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Account Status</label>
                        <p>
                            <span class="status-badge <?php echo $agent['status']; ?>">
                                <?php echo ucfirst($agent['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="user-stats" style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px;">Performance Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h4><?php echo $total_clients; ?></h4>
                            <p>Total Clients</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo number_format($total_earned); ?> RWF</h4>
                            <p>Total Earned</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo $agent_profile['commission_rate']; ?>%</h4>
                            <p>Commission Rate</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo date('M d, Y', strtotime($agent['created_at'])); ?></h4>
                            <p>Join Date</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="form-container" style="margin-bottom: 32px;">
                <h2>Edit Profile Information</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Update your contact information
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            value="<?php echo htmlspecialchars($agent['full_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input 
                                type="tel" 
                                name="phone" 
                                pattern="[0-9]{10}"
                                value="<?php echo htmlspecialchars($agent['phone']); ?>"
                                required
                            >
                            <small>Used for login</small>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($agent['email']); ?>"
                            >
                            <small>For notifications</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Details Form -->
            <div class="form-container" style="margin-bottom: 32px;">
                <h2>Payment Details</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Configure your payment information for commission payouts
                </p>

                <form method="POST">
                    
                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Mobile Money</h3>
                    
                    <div class="form-group">
                        <label>Mobile Money Number</label>
                        <input 
                            type="tel" 
                            name="momo_number" 
                            placeholder="078XXXXXXX"
                            pattern="[0-9]{10}"
                            value="<?php echo htmlspecialchars($agent_profile['momo_number'] ?? ''); ?>"
                        >
                        <small>For Mobile Money (MTN/Airtel) payouts</small>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Bank Account</h3>

                    <div class="form-group">
                        <label>Bank Name</label>
                        <input 
                            type="text" 
                            name="bank_name" 
                            placeholder="e.g., Bank of Kigali"
                            value="<?php echo htmlspecialchars($agent_profile['bank_name'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Account Number</label>
                            <input 
                                type="text" 
                                name="account_number" 
                                placeholder="Enter account number"
                                value="<?php echo htmlspecialchars($agent_profile['account_number'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Account Name</label>
                            <input 
                                type="text" 
                                name="account_name" 
                                placeholder="Name on account"
                                value="<?php echo htmlspecialchars($agent_profile['account_name'] ?? ''); ?>"
                            >
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Additional Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>National ID Number</label>
                            <input 
                                type="text" 
                                name="id_number" 
                                placeholder="Enter ID number"
                                value="<?php echo htmlspecialchars($agent_profile['id_number'] ?? ''); ?>"
                            >
                            <small>For verification purposes</small>
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <input 
                                type="text" 
                                name="address" 
                                placeholder="Your address"
                                value="<?php echo htmlspecialchars($agent_profile['address'] ?? ''); ?>"
                            >
                        </div>
                    </div>

                    <div style="padding: 16px; background: #EFF6FF; border-radius: 8px; margin: 24px 0;">
                        <p style="color: #1E40AF; margin: 0;">
                            <strong>💡 Tip:</strong> Add at least one payment method (Mobile Money or Bank Account) to receive commission payouts.
                        </p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_payment" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Update Payment Details
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="form-container">
                <h2>Change Password</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Update your account password for security
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input 
                            type="password" 
                            name="current_password" 
                            placeholder="Enter current password"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password *</label>
                            <input 
                                type="password" 
                                name="new_password" 
                                placeholder="Enter new password"
                                minlength="6"
                                required
                            >
                            <small>Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                placeholder="Re-enter new password"
                                minlength="6"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Section -->
            <div style="margin-top: 40px; padding: 24px; background: #F9FAFB; border-radius: 12px; border-left: 4px solid #6366F1;">
                <h3 style="margin-bottom: 12px; color: #1F2937;">📞 Need Help?</h3>
                <p style="color: #6B7280; margin-bottom: 16px;">
                    For questions about commissions, payouts, or account issues, contact support.
                </p>
                <div style="display: flex; gap: 12px;">
                    <a href="mailto:support@uruhushya.com" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Email Support
                    </a>
                    <a href="tel:+250788000000" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        Call Support
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
