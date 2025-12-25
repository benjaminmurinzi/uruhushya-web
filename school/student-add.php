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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $student_code = trim($_POST['student_code']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
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
                    
                    // Create user account
                    $stmt = $conn->prepare("
                        INSERT INTO users (full_name, email, phone, password, user_type, status, created_at) 
                        VALUES (?, ?, ?, ?, 'student', 'active', NOW())
                    ");
                    $stmt->execute([$full_name, $email, $phone, $hashed_password]);
                    $student_id = $conn->lastInsertId();
                    
                    // Generate student code if not provided
                    if (empty($student_code)) {
                        $student_code = 'STD' . str_pad($student_id, 4, '0', STR_PAD_LEFT);
                    }
                    
                    // Link student to school
                    $stmt = $conn->prepare("
                        INSERT INTO school_students 
                        (school_id, student_id, student_code, status, added_by, notes, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$school_id, $student_id, $student_code, $status, $school_id, $notes]);
                    
                    header('Location: students.php?success=added');
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Add student error: " . $e->getMessage());
            $error = 'Failed to add student. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - URUHUSHYA School</title>
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
            
            <a href="students.php" class="nav-item active">
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
            <h1>Add New Student</h1>
            <div class="admin-info">
                <a href="students.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Students
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="form-container">
                <h2>Student Registration</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">
                    Add a new student to your driving school
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
                    
                    <h3 style="margin-bottom: 16px; color: #1F2937;">Personal Information</h3>
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            placeholder="Enter student's full name"
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
                            <small>10-digit phone number (will be used for login)</small>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="student@example.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            >
                            <small>Optional - for notifications</small>
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Account Information</h3>

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

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">School Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Student Code</label>
                            <input 
                                type="text" 
                                name="student_code" 
                                placeholder="Leave empty to auto-generate"
                                value="<?php echo htmlspecialchars($_POST['student_code'] ?? ''); ?>"
                            >
                            <small>School-specific student ID (e.g., STD0001)</small>
                        </div>

                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea 
                            name="notes" 
                            rows="4"
                            placeholder="Add any notes about this student..."
                        ><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        <small>Internal notes (not visible to student)</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Add Student
                        </button>
                        <a href="students.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>