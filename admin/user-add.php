<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$user_type = isset($_GET['type']) ? $_GET['type'] : 'students';
$user_type_singular = rtrim($user_type, 's'); // Remove 's' to get singular form

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $selected_user_type = $_POST['user_type'];
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered';
            } else {
                // Check if phone already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                if ($stmt->fetch()) {
                    $error = 'Phone number is already registered';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $stmt = $conn->prepare("
                        INSERT INTO users (full_name, email, phone, password, user_type, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$full_name, $email, $phone, $hashed_password, $selected_user_type, $status]);
                    
                    $success = ucfirst($selected_user_type) . ' added successfully!';
                    
                    // Clear form
                    $full_name = $email = $phone = '';
                }
            }
        } catch (Exception $e) {
            error_log("Add user error: " . $e->getMessage());
            $error = 'Failed to add user. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - URUHUSHYA Admin</title>
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
            
            <a href="users.php" class="nav-item active">
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
            <h1>Add New User</h1>
            <div class="admin-info">
                <a href="users.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Users
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="form-container">
                <h2>Create New User Account</h2>
                
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

                <form method="POST" class="user-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input 
                                type="text" 
                                name="full_name" 
                                value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                                placeholder="Enter full name"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>User Type *</label>
                            <select name="user_type" required>
                                <option value="student" <?php echo $user_type_singular === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="school" <?php echo $user_type_singular === 'school' ? 'selected' : ''; ?>>School</option>
                                <option value="agent" <?php echo $user_type_singular === 'agent' ? 'selected' : ''; ?>>Agent</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email *</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                placeholder="example@email.com"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Phone *</label>
                            <input 
                                type="tel" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                placeholder="07xx xxx xxx"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <input 
                                type="password" 
                                name="password" 
                                placeholder="Minimum 6 characters"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                placeholder="Re-enter password"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Account Status *</label>
                        <select name="status" required>
                            <option value="active">Active (Can login immediately)</option>
                            <option value="pending">Pending (Requires approval)</option>
                            <option value="inactive">Inactive (Cannot login)</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Create User
                        </button>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>