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
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($student_id <= 0) {
    header('Location: students.php?error=invalid_student');
    exit;
}

// Get student details
try {
    $stmt = $conn->prepare("
        SELECT ss.*, u.full_name, u.email, u.phone
        FROM school_students ss
        JOIN users u ON ss.student_id = u.id
        WHERE ss.school_id = ? AND ss.student_id = ?
    ");
    $stmt->execute([$school_id, $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Location: students.php?error=student_not_found');
        exit;
    }
} catch (Exception $e) {
    error_log("Edit student error: " . $e->getMessage());
    header('Location: students.php?error=system_error');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = trim($_POST['new_password']);
    $student_code = trim($_POST['student_code']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    // Validation
    if (empty($full_name) || empty($phone)) {
        $error = 'Full name and phone number are required';
    } else {
        try {
            // Check if phone already exists (excluding current student)
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $student_id]);
            if ($stmt->fetch()) {
                $error = 'Phone number already in use';
            } else {
                // Check email if provided
                if (!empty($email)) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $student_id]);
                    if ($stmt->fetch()) {
                        $error = 'Email already in use';
                    }
                }
                
                if (!$error) {
                    // Update user account
                    if (!empty($new_password)) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("
                            UPDATE users 
                            SET full_name = ?, email = ?, phone = ?, password = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$full_name, $email, $phone, $hashed_password, $student_id]);
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE users 
                            SET full_name = ?, email = ?, phone = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$full_name, $email, $phone, $student_id]);
                    }
                    
                    // Update school_students record
                    $stmt = $conn->prepare("
                        UPDATE school_students 
                        SET student_code = ?, status = ?, notes = ? 
                        WHERE school_id = ? AND student_id = ?
                    ");
                    $stmt->execute([$student_code, $status, $notes, $school_id, $student_id]);
                    
                    $success = 'Student updated successfully';
                    
                    // Refresh student data
                    $stmt = $conn->prepare("
                        SELECT ss.*, u.full_name, u.email, u.phone
                        FROM school_students ss
                        JOIN users u ON ss.student_id = u.id
                        WHERE ss.school_id = ? AND ss.student_id = ?
                    ");
                    $stmt->execute([$school_id, $student_id]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        } catch (Exception $e) {
            error_log("Update student error: " . $e->getMessage());
            $error = 'Failed to update student. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - URUHUSHYA School</title>
    <link rel="stylesheet" href="../assets/css/school-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as before) -->
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
            <h1>Edit Student</h1>
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
                <h2>Edit Student Information</h2>
                
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

                <form method="POST">
                    
                    <h3 style="margin-bottom: 16px; color: #1F2937;">Personal Information</h3>
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            value="<?php echo htmlspecialchars($student['full_name']); ?>"
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
                                value="<?php echo htmlspecialchars($student['phone']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($student['email']); ?>"
                            >
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Change Password (Optional)</h3>

                    <div class="form-group">
                        <label>New Password</label>
                        <input 
                            type="password" 
                            name="new_password" 
                            placeholder="Leave empty to keep current password"
                            minlength="6"
                        >
                        <small>Only fill this if you want to change the password</small>
                    </div>

                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">School Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Student Code *</label>
                            <input 
                                type="text" 
                                name="student_code" 
                                value="<?php echo htmlspecialchars($student['student_code']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $student['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea 
                            name="notes" 
                            rows="4"
                        ><?php echo htmlspecialchars($student['notes']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Changes
                        </button>
                        <a href="students.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>