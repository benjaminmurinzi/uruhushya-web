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
            // Check if test code already exists
            $stmt = $conn->prepare("SELECT id FROM test_templates WHERE test_code = ?");
            $stmt->execute([$test_code]);
            if ($stmt->fetch()) {
                $error = 'Test code already exists';
            } else {
                // Insert test
                $stmt = $conn->prepare("
                    INSERT INTO test_templates 
                    (test_code, name_rw, name_en, description_rw, description_en, total_questions, time_limit_minutes, passing_score) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $test_code, 
                    $name_rw, 
                    $name_en, 
                    $description_rw, 
                    $description_en, 
                    $total_questions, 
                    $time_limit_minutes, 
                    $passing_score
                ]);
                
                header('Location: tests.php?success=created');
                exit;
            }
        } catch (Exception $e) {
            error_log("Add test error: " . $e->getMessage());
            $error = 'Failed to create test. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Test - URUHUSHYA Admin</title>
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
            <h1>Add New Test</h1>
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
                <h2>Create New Test</h2>
                
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

                <form method="POST" class="test-form">
                    <div class="form-group">
                        <label>Test Code * (e.g., K021)</label>
                        <input 
                            type="text" 
                            name="test_code" 
                            placeholder="K021"
                            style="text-transform: uppercase;"
                            required
                        >
                        <small>Must be unique. Format: K followed by number (e.g., K021)</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Name (Kinyarwanda) *</label>
                            <input 
                                type="text" 
                                name="name_rw" 
                                placeholder="Isuzuma #K021"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Test Name (English) *</label>
                            <input 
                                type="text" 
                                name="name_en" 
                                placeholder="Test #K021"
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
                                placeholder="Ibisobanuro by'ikizamini mu Kinyarwanda..."
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label>Description (English)</label>
                            <textarea 
                                name="description_en" 
                                rows="3"
                                placeholder="Test description in English..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Total Questions *</label>
                            <input 
                                type="number" 
                                name="total_questions" 
                                value="20"
                                min="1"
                                max="100"
                                required
                            >
                            <small>Number of questions in this test (1-100)</small>
                        </div>

                        <div class="form-group">
                            <label>Time Limit (Minutes) *</label>
                            <input 
                                type="number" 
                                name="time_limit_minutes" 
                                value="30"
                                min="1"
                                max="180"
                                required
                            >
                            <small>Time allowed to complete test (1-180 min)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Passing Score (%) *</label>
                        <input 
                            type="number" 
                            name="passing_score" 
                            value="15"
                            min="1"
                            max="100"
                            required
                        >
                        <small>Minimum score required to pass (1-100%)</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Create Test
                        </button>
                        <a href="tests.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>