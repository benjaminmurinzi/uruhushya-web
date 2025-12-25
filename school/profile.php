<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$school_id = $_SESSION['user_id'];
$school_name = $_SESSION['full_name'];
$error = '';
$success = '';

// Get school details
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$school) {
        header('Location: logout.php');
        exit;
    }
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
        $error = 'School name and phone number are required';
    } else {
        try {
            // Check if phone already exists (excluding current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $school_id]);
            if ($stmt->fetch()) {
                $error = 'Phone number already in use';
            } else {
                // Check email if provided
                if (!empty($email)) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $school_id]);
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
                    $stmt->execute([$full_name, $email, $phone, $school_id]);
                    
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['phone'] = $phone;
                    
                    $success = 'Profile updated successfully';
                    
                    // Refresh school data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$school_id]);
                    $school = $stmt->fetch(PDO::FETCH_ASSOC);
                    $school_name = $full_name;
                }
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Failed to update profile. Please try again.';
        }
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
            if (!password_verify($current_password, $school['password'])) {
                $error = 'Current password is incorrect';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $school_id]);
                
                $success = 'Password changed successfully';
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = 'Failed to change password. Please try again.';
        }
    }
}

// Get account statistics
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM school_students WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $student_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM student_test_attempts sta
        JOIN school_students ss ON sta.student_id = ss.student_id
        WHERE ss.school_id = ?
    ");
    $stmt->execute([$school_id]);
    $test_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (Exception $e) {
    error_log("Stats error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
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
            
            <a href="students.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Students
            </a>
            
            <a href="test-assignment.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Test Assignment
            </a>
            
            <a href="reports.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Reports
            </a>
            
            <a href="subscription.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Subscription
            </a>
            
            <a href="bulk-operations.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Bulk Operations
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
                <span>🏫 <strong><?php echo htmlspecialchars($school_name); ?></strong></span>
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
                    <div class="profile-avatar" style="width: 100px; height: 100px; font-size: 40px;">
                        <?php 
                        $initials = strtoupper(substr($school['full_name'], 0, 2));
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($school['full_name']); ?></h2>
                        <p class="user-type">
                            <span class="status-badge active">School Account</span>
                        </p>
                        <p style="color: #6B7280; margin-top: 8px;">
                            Member since: <?php echo date('F Y', strtotime($school['created_at'])); ?>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #10B981;">
                                <?php echo $student_count; ?>
                            </div>
                            <div style="color: #6B7280; font-size: 14px;">Students</div>
                        </div>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <label>School Name</label>
                        <p><strong><?php echo htmlspecialchars($school['full_name']); ?></strong></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($school['phone']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($school['email'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <label>Account Status</label>
                        <p>
                            <span class="status-badge <?php echo $school['status']; ?>">
                                <?php echo ucfirst($school['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="user-stats" style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px;">Account Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h4><?php echo $student_count; ?></h4>
                            <p>Total Students</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo $test_count; ?></h4>
                            <p>Test Attempts</p>
                        </div>
                        
                        <div class="stat-box">
                            <h4><?php echo date('M d, Y', strtotime($school['created_at'])); ?></h4>
                            <p>Joined Date</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="form-container" style="margin-bottom: 32px;">
                <h2>Edit Profile Information</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Update your school's contact information
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label>School Name *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            value="<?php echo htmlspecialchars($school['full_name']); ?>"
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
                                value="<?php echo htmlspecialchars($school['phone']); ?>"
                                required
                            >
                            <small>Used for login</small>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($school['email']); ?>"
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

            <!-- Danger Zone -->
            <div style="margin-top: 40px; padding: 24px; background: #FEE2E2; border-radius: 12px; border-left: 4px solid #EF4444;">
                <h3 style="margin-bottom: 12px; color: #991B1B;">⚠️ Danger Zone</h3>
                <p style="color: #991B1B; margin-bottom: 16px;">
                    Need to deactivate your account or need support? Contact the administrator.
                </p>
                <div style="display: flex; gap: 12px;">
                    <a href="mailto:admin@uruhushya.com" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Contact Support
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>