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

// Categories
$categories = ['Traffic Signs', 'General Rules', 'Road Safety', 'City Driving', 'Penalties', 'Emergencies'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text_rw = trim($_POST['question_text_rw']);
    $question_text_en = trim($_POST['question_text_en']);
    $option_a_rw = trim($_POST['option_a_rw']);
    $option_a_en = trim($_POST['option_a_en']);
    $option_b_rw = trim($_POST['option_b_rw']);
    $option_b_en = trim($_POST['option_b_en']);
    $option_c_rw = trim($_POST['option_c_rw']);
    $option_c_en = trim($_POST['option_c_en']);
    $option_d_rw = trim($_POST['option_d_rw']);
    $option_d_en = trim($_POST['option_d_en']);
    $correct_answer = strtoupper(trim($_POST['correct_answer']));
    $explanation_rw = trim($_POST['explanation_rw']);
    $explanation_en = trim($_POST['explanation_en']);
    $category = trim($_POST['category']);
    
    // Validation
    if (empty($question_text_rw) || empty($question_text_en)) {
        $error = 'Question text in both languages is required';
    } elseif (empty($option_a_rw) || empty($option_b_rw) || empty($option_c_rw) || empty($option_d_rw)) {
        $error = 'All options in Kinyarwanda are required';
    } elseif (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
        $error = 'Correct answer must be A, B, C, or D';
    } else {
        try {
            // Handle image upload
            $image_path = null;
            if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/questions/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                    $error = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP';
                } else {
                    $new_filename = 'q_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['question_image']['tmp_name'], $upload_path)) {
                        $image_path = 'assets/images/questions/' . $new_filename;
                    } else {
                        $error = 'Failed to upload image';
                    }
                }
            }
            
            if (!$error) {
                // Insert question
                $stmt = $conn->prepare("
                    INSERT INTO questions 
                    (question_text_rw, question_text_en, question_image, 
                     option_a_rw, option_a_en, option_b_rw, option_b_en, 
                     option_c_rw, option_c_en, option_d_rw, option_d_en, 
                     correct_answer, explanation_rw, explanation_en, category, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $question_text_rw, $question_text_en, $image_path,
                    $option_a_rw, $option_a_en, $option_b_rw, $option_b_en,
                    $option_c_rw, $option_c_en, $option_d_rw, $option_d_en,
                    $correct_answer, $explanation_rw, $explanation_en, $category
                ]);
                
                header('Location: questions.php?success=created');
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Add question error: " . $e->getMessage());
            $error = 'Failed to create question. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Question - URUHUSHYA Admin</title>
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
            
            <a href="questions.php" class="nav-item active">
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
            <h1>Add New Question</h1>
            <div class="admin-info">
                <a href="questions.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Questions
                </a>
            </div>
        </header>

        <section class="content-section">
            <div class="form-container">
                <h2>Create New Question</h2>
                
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

                <form method="POST" enctype="multipart/form-data" class="question-form">
                    
                    <!-- Question Text -->
                    <h3 style="margin-bottom: 16px; color: #1F2937;">Question Text</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Question (Kinyarwanda) *</label>
                            <textarea 
                                name="question_text_rw" 
                                rows="4"
                                placeholder="Andika ikibazo mu Kinyarwanda..."
                                required
                            ><?php echo htmlspecialchars($_POST['question_text_rw'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Question (English) *</label>
                            <textarea 
                                name="question_text_en" 
                                rows="4"
                                placeholder="Write question in English..."
                                required
                            ><?php echo htmlspecialchars($_POST['question_text_en'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Question Image -->
                    <div class="form-group">
                        <label>Question Image (Optional)</label>
                        <input 
                            type="file" 
                            name="question_image" 
                            accept="image/*"
                        >
                        <small>Upload traffic sign or relevant image (JPG, PNG, GIF, WEBP)</small>
                    </div>

                    <!-- Options -->
                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Answer Options</h3>
                    
                    <!-- Option A -->
                    <div class="option-group">
                        <h4>Option A</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Option A (Kinyarwanda) *</label>
                                <input 
                                    type="text" 
                                    name="option_a_rw" 
                                    placeholder="Igisubizo A mu Kinyarwanda"
                                    value="<?php echo htmlspecialchars($_POST['option_a_rw'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label>Option A (English)</label>
                                <input 
                                    type="text" 
                                    name="option_a_en" 
                                    placeholder="Answer A in English"
                                    value="<?php echo htmlspecialchars($_POST['option_a_en'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Option B -->
                    <div class="option-group">
                        <h4>Option B</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Option B (Kinyarwanda) *</label>
                                <input 
                                    type="text" 
                                    name="option_b_rw" 
                                    placeholder="Igisubizo B mu Kinyarwanda"
                                    value="<?php echo htmlspecialchars($_POST['option_b_rw'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label>Option B (English)</label>
                                <input 
                                    type="text" 
                                    name="option_b_en" 
                                    placeholder="Answer B in English"
                                    value="<?php echo htmlspecialchars($_POST['option_b_en'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Option C -->
                    <div class="option-group">
                        <h4>Option C</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Option C (Kinyarwanda) *</label>
                                <input 
                                    type="text" 
                                    name="option_c_rw" 
                                    placeholder="Igisubizo C mu Kinyarwanda"
                                    value="<?php echo htmlspecialchars($_POST['option_c_rw'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label>Option C (English)</label>
                                <input 
                                    type="text" 
                                    name="option_c_en" 
                                    placeholder="Answer C in English"
                                    value="<?php echo htmlspecialchars($_POST['option_c_en'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Option D -->
                    <div class="option-group">
                        <h4>Option D</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Option D (Kinyarwanda) *</label>
                                <input 
                                    type="text" 
                                    name="option_d_rw" 
                                    placeholder="Igisubizo D mu Kinyarwanda"
                                    value="<?php echo htmlspecialchars($_POST['option_d_rw'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label>Option D (English)</label>
                                <input 
                                    type="text" 
                                    name="option_d_en" 
                                    placeholder="Answer D in English"
                                    value="<?php echo htmlspecialchars($_POST['option_d_en'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Correct Answer & Category -->
                    <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Additional Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Correct Answer *</label>
                            <select name="correct_answer" required>
                                <option value="">Select correct answer</option>
                                <option value="A" <?php echo ($_POST['correct_answer'] ?? '') === 'A' ? 'selected' : ''; ?>>A</option>
                                <option value="B" <?php echo ($_POST['correct_answer'] ?? '') === 'B' ? 'selected' : ''; ?>>B</option>
                                <option value="C" <?php echo ($_POST['correct_answer'] ?? '') === 'C' ? 'selected' : ''; ?>>C</option>
                                <option value="D" <?php echo ($_POST['correct_answer'] ?? '') === 'D' ? 'selected' : ''; ?>>D</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>" <?php echo ($_POST['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Explanations -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Explanation (Kinyarwanda)</label>
                            <textarea 
                                name="explanation_rw" 
                                rows="3"
                                placeholder="Sobanura igisubizo nyacyo mu Kinyarwanda..."
                            ><?php echo htmlspecialchars($_POST['explanation_rw'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Explanation (English)</label>
                            <textarea 
                                name="explanation_en" 
                                rows="3"
                                placeholder="Explain the correct answer in English..."
                            ><?php echo htmlspecialchars($_POST['explanation_en'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Create Question
                        </button>
                        <a href="questions.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <style>
        .option-group {
            padding: 20px;
            background: #F9FAFB;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .option-group h4 {
            margin-bottom: 12px;
            color: #3B82F6;
            font-size: 16px;
        }
    </style>
</body>
</html>