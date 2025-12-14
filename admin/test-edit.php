<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];
$test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($test_id <= 0) {
    header('Location: tests.php?error=invalid_test');
    exit;
}

// Get test details
try {
    $stmt = $conn->prepare("SELECT * FROM test_templates WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        header('Location: tests.php?error=test_not_found');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Test edit error: " . $e->getMessage());
    header('Location: tests.php?error=system_error');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_code = strtoupper(trim($_POST['test_code']));
    $name_rw = trim($_POST['name_rw']);
    $name_en = trim($_POST['name_en']);
    $description_rw = trim($_POST['description_rw']);
    $description_en = trim($_POST['description_en']);
    $total_questions = (int)$_POST['total_questions'];
    $time_limit_minutes = (int)$_POST['time_limit_minutes'];
    $passing_score = (int)$_POST['passing_score'];
    
    // Validation
    if (empty($test_code) || empty($name_rw) || empty($name_en)) {
        $error = 'Test code and names are required';
    } elseif ($total_questions < 1 || $total_questions > 100) {
        $error = 'Total questions must be between 1 and 100';
    } elseif ($time_limit_minutes < 1 || $time_limit_minutes > 180) {
        $error = 'Time limit must be between 1 and 180 minutes';
    } elseif ($passing_score < 1 || $passing_score > 100) {
        $error = 'Passing score must be between 1 and 100%';
    } else {
        try {
            // Check if test code is taken by another test
            $stmt = $conn->prepare("SELECT id FROM test_templates WHERE test_code = ? AND id != ?");
            $stmt->execute([$test_code, $test_id]);
            if ($stmt->fetch()) {
                $error = 'Test code already exists';
            } else {
                // Update test
                $stmt = $conn->prepare("
                    UPDATE test_templates SET 
                        test_code = ?, 
                        name_rw = ?, 
                        name_en = ?, 
                        description_rw = ?, 
                        description_en = ?, 
                        total_questions = ?, 
                        time_limit_minutes = ?, 
                        passing_score = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $test_code, 
                    $name_rw, 
                    $name_en, 
                    $description_rw, 
                    $description_en, 
                    $total_questions, 
                    $time_limit_minutes, 
                    $passing_score,
                    $test_id
                ]);
                
                $success = 'Test updated successfully';
                
                // Refresh test data
                $stmt = $conn->prepare("SELECT * FROM test_templates WHERE id = ?");
                $stmt->execute([$test_id]);
                $test = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Update test error: " . $e->getMessage());
            $error = 'Failed to update test. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Test - URUHUSHYA Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
    <!-- Sidebar (same as test-add.php) -->
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
            
            <a href="tests.php" class="nav-item active">
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
            <h1>Edit Test</h1>
            <div class="admin-info">
                <a href="tests.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Tests
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="form-container">
                <h2>Edit Test: <?php echo htmlspecialchars($test['test_code']); ?></h2>
                
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

                <form method="POST" class="test-form">
                    <div class="form-group">
                        <label>Test Code *</label>
                        <input 
                            type="text" 
                            name="test_code" 
                            value="<?php echo htmlspecialchars($test['test_code']); ?>"
                            style="text-transform: uppercase;"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Name (Kinyarwanda) *</label>
                            <input 
                                type="text" 
                                name="name_rw" 
                                value="<?php echo htmlspecialchars($test['name_rw']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Test Name (English) *</label>
                            <input 
                                type="text" 
                                name="name_en" 
                                value="<?php echo htmlspecialchars($test['name_en']); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Description (Kinyarwanda)</label>
                            <textarea 
                                name="description_rw" 
                                rows="3"
                            ><?php echo htmlspecialchars($test['description_rw']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Description (English)</label>
                            <textarea 
                                name="description_en" 
                                rows="3"
                            ><?php echo htmlspecialchars($test['description_en']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Total Questions *</label>
                            <input 
                                type="number" 
                                name="total_questions" 
                                value="<?php echo $test['total_questions']; ?>"
                                min="1"
                                max="100"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Time Limit (Minutes) *</label>
                            <input 
                                type="number" 
                                name="time_limit_minutes" 
                                value="<?php echo $test['time_limit_minutes']; ?>"
                                min="1"
                                max="180"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Passing Score (%) *</label>
                        <input 
                            type="number" 
                            name="passing_score" 
                            value="<?php echo $test['passing_score']; ?>"
                            min="1"
                            max="100"
                            required
                        >
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
                        <a href="tests.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>