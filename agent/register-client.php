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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_type = $_POST['client_type']; // 'student' or 'school'
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if (empty($full_name) || empty($phone) || empty($password)) {
        $error = 'Full name, phone number, and password are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            // Check if phone already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = 'Phone number already registered';
            } else {
                // Check if email already exists (if provided)
                if (!empty($email)) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'Email already registered';
                    }
                }
                
                if (!$error) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Create user account with agent referral
                    $stmt = $conn->prepare("
                        INSERT INTO users 
                        (full_name, email, phone, password, user_type, status, referred_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())
                    ");
                    $stmt->execute([$full_name, $email, $phone, $hashed_password, $client_type, $agent_id]);
                    $client_id = $conn->lastInsertId();
                    
                    // Redirect to subscription creation
                    header("Location: create-subscription.php?client_id={$client_id}&success=client_registered");
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Register client error: " . $e->getMessage());
            $error = 'Failed to register client. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Client - URUHUSHYA Agent</title>
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
            
            <a href="clients.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Clients
            </a>
            
            <a href="commissions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Commissions
            </a>
            
            <a href="profile.php" class="nav-item">
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
            <h1>Register New Client</h1>
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
            <div class="form-container">
                <h2>Client Registration</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Register a new student or school to earn commission on their subscriptions
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

                <form method="POST">
                    
                    <div class="form-group">
                        <label>Client Type *</label>
                        <select name="client_type" required onchange="updateFormLabels(this.value)">
                            <option value="">-- Select client type --</option>
                            <option value="student">Student (Individual)</option>
                            <option value="school">Driving School</option>
                        </select>
                        <small>Select whether this is an individual student or a driving school</small>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Client Information</h3>
                    
                    <div class="form-group">
                        <label id="nameLabel">Full Name / School Name *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            placeholder="Enter client name"
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input 
                                type="tel" 
                                name="phone" 
                                placeholder="078XXXXXXX"
                                pattern="[0-9]{10}"
                                value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                required
                            >
                            <small>10-digit phone number (used for login)</small>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="client@example.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            >
                            <small>Optional - for notifications</small>
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Account Security</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <input 
                                type="password" 
                                name="password" 
                                placeholder="Enter password"
                                minlength="6"
                                required
                            >
                            <small>Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                placeholder="Re-enter password"
                                minlength="6"
                                required
                            >
                        </div>
                    </div>

                    <div style="padding: 16px; background: #EFF6FF; border-radius: 8px; margin: 24px 0;">
                        <p style="color: #1E40AF; margin: 0;">
                            <strong>💡 Next Step:</strong> After registration, you'll be able to create a subscription for this client and earn your commission.
                        </p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Register Client & Continue
                        </button>
                        <a href="clients.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        function updateFormLabels(clientType) {
            const nameLabel = document.getElementById('nameLabel');
            if (clientType === 'student') {
                nameLabel.textContent = 'Student Full Name *';
            } else if (clientType === 'school') {
                nameLabel.textContent = 'Driving School Name *';
            } else {
                nameLabel.textContent = 'Full Name / School Name *';
            }
        }
    </script>
</body>
</html>