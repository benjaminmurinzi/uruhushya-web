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
$preview_data = [];

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $file_extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        
        if ($file_extension !== 'csv') {
            $error = 'Please upload a valid CSV file';
        } else {
            try {
                $handle = fopen($file_tmp, 'r');
                $row_number = 0;
                $headers = [];
                
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $row_number++;
                    
                    // First row is header
                    if ($row_number === 1) {
                        $headers = $data;
                        continue;
                    }
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }
                    
                    // Map CSV data to array
                    $student_data = [
                        'full_name' => trim($data[0] ?? ''),
                        'phone' => trim($data[1] ?? ''),
                        'email' => trim($data[2] ?? ''),
                        'password' => trim($data[3] ?? ''),
                        'student_code' => trim($data[4] ?? ''),
                        'row' => $row_number
                    ];
                    
                    // Validate data
                    $student_data['errors'] = [];
                    
                    if (empty($student_data['full_name'])) {
                        $student_data['errors'][] = 'Name is required';
                    }
                    
                    if (empty($student_data['phone'])) {
                        $student_data['errors'][] = 'Phone is required';
                    } elseif (!preg_match('/^[0-9]{10}$/', $student_data['phone'])) {
                        $student_data['errors'][] = 'Invalid phone format (10 digits required)';
                    }
                    
                    if (empty($student_data['password'])) {
                        $student_data['errors'][] = 'Password is required';
                    } elseif (strlen($student_data['password']) < 6) {
                        $student_data['errors'][] = 'Password must be at least 6 characters';
                    }
                    
                    // Check if phone already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->execute([$student_data['phone']]);
                    if ($stmt->fetch()) {
                        $student_data['errors'][] = 'Phone already registered';
                    }
                    
                    // Check if email already exists (if provided)
                    if (!empty($student_data['email'])) {
                        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$student_data['email']]);
                        if ($stmt->fetch()) {
                            $student_data['errors'][] = 'Email already registered';
                        }
                    }
                    
                    $preview_data[] = $student_data;
                }
                
                fclose($handle);
                
                if (empty($preview_data)) {
                    $error = 'No valid data found in CSV file';
                } else {
                    $_SESSION['bulk_upload_data'] = $preview_data;
                    $success = count($preview_data) . ' students loaded for preview';
                }
                
            } catch (Exception $e) {
                error_log("CSV upload error: " . $e->getMessage());
                $error = 'Failed to process CSV file. Please try again.';
            }
        }
    } else {
        $error = 'Failed to upload file. Please try again.';
    }
}

// Handle bulk import confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    
    if (!isset($_SESSION['bulk_upload_data'])) {
        $error = 'No upload data found. Please upload CSV again.';
    } else {
        try {
            $imported_count = 0;
            $failed_count = 0;
            
            foreach ($_SESSION['bulk_upload_data'] as $student_data) {
                // Skip rows with errors
                if (!empty($student_data['errors'])) {
                    $failed_count++;
                    continue;
                }
                
                // Hash password
                $hashed_password = password_hash($student_data['password'], PASSWORD_DEFAULT);
                
                // Create user account
                $stmt = $conn->prepare("
                    INSERT INTO users (full_name, email, phone, password, user_type, status, created_at) 
                    VALUES (?, ?, ?, ?, 'student', 'active', NOW())
                ");
                $stmt->execute([
                    $student_data['full_name'],
                    $student_data['email'],
                    $student_data['phone'],
                    $hashed_password
                ]);
                $student_id = $conn->lastInsertId();
                
                // Generate student code if not provided
                $student_code = !empty($student_data['student_code']) 
                    ? $student_data['student_code'] 
                    : 'STD' . str_pad($student_id, 4, '0', STR_PAD_LEFT);
                
                // Link student to school
                $stmt = $conn->prepare("
                    INSERT INTO school_students 
                    (school_id, student_id, student_code, status, added_by, created_at) 
                    VALUES (?, ?, ?, 'active', ?, NOW())
                ");
                $stmt->execute([$school_id, $student_id, $student_code, $school_id]);
                
                $imported_count++;
            }
            
            // Clear session data
            unset($_SESSION['bulk_upload_data']);
            
            header("Location: bulk-operations.php?success=imported&count={$imported_count}&failed={$failed_count}");
            exit;
            
        } catch (Exception $e) {
            error_log("Bulk import error: " . $e->getMessage());
            $error = 'Failed to import students. Please try again.';
        }
    }
}

// Load preview data from session
if (isset($_SESSION['bulk_upload_data'])) {
    $preview_data = $_SESSION['bulk_upload_data'];
}

$success = $_GET['success'] ?? $success;
if ($success === 'imported') {
    $imported_count = $_GET['count'] ?? 0;
    $failed_count = $_GET['failed'] ?? 0;
    $success = "Successfully imported {$imported_count} students! " . ($failed_count > 0 ? "{$failed_count} failed." : "");
    unset($_SESSION['bulk_upload_data']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-case=1.0">
    <title>Bulk Operations - URUHUSHYA School</title>
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
            
            <a href="bulk-operations.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Bulk Operations
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
            <h1>Bulk Operations</h1>
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

            <?php if (empty($preview_data)): ?>
                <!-- Upload Form -->
                <div class="form-container">
                    <h2>📤 Bulk Student Upload</h2>
                    <p style="color: #6B7280; margin-bottom: 24px;">
                        Upload a CSV file to add multiple students at once
                    </p>

                    <!-- Instructions -->
                    <div style="padding: 24px; background: #EFF6FF; border-radius: 12px; margin-bottom: 32px;">
                        <h3 style="margin-bottom: 16px; color: #1E40AF;">📋 Instructions</h3>
                        <ol style="color: #1F2937; line-height: 2; margin-left: 20px;">
                            <li>Download the CSV template below</li>
                            <li>Fill in student information (do not change column headers)</li>
                            <li>Save the file as CSV format</li>
                            <li>Upload the completed CSV file</li>
                            <li>Review the preview before final import</li>
                        </ol>
                    </div>

                    <!-- Download Template -->
                    <div style="text-align: center; margin-bottom: 40px;">
                        <a href="download-template.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download CSV Template
                        </a>
                    </div>

                    <!-- Upload Zone -->
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-zone" id="uploadZone">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #10B981; margin-bottom: 16px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <h3 style="margin-bottom: 8px; color: #1F2937;">Drop CSV file here</h3>
                            <p style="color: #6B7280; margin-bottom: 16px;">or click to browse</p>
                            <input 
                                type="file" 
                                name="csv_file" 
                                id="csvFile" 
                                accept=".csv"
                                required
                                style="display: none;"
                            >
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('csvFile').click()">
                                Choose File
                            </button>
                        </div>

                        <div id="fileInfo" style="display: none; margin-top: 16px; padding: 16px; background: #F9FAFB; border-radius: 8px;">
                            <strong>Selected file:</strong> <span id="fileName"></span>
                        </div>

                        <div class="form-actions" style="margin-top: 24px;">
                            <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload & Preview
                            </button>
                        </div>
                    </form>

                    <!-- CSV Format Guide -->
                    <div style="margin-top: 40px; padding: 24px; background: #F9FAFB; border-radius: 12px;">
                        <h3 style="margin-bottom: 16px; color: #1F2937;">📄 CSV Format</h3>
                        <p style="color: #6B7280; margin-bottom: 12px;">Your CSV file should have these columns:</p>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: white;">
                                    <th style="padding: 12px; text-align: left; border: 1px solid #E5E7EB;">Column</th>
                                    <th style="padding: 12px; text-align: left; border: 1px solid #E5E7EB;">Required</th>
                                    <th style="padding: 12px; text-align: left; border: 1px solid #E5E7EB;">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="background: white;">
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><strong>Full Name</strong></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><span style="color: #EF4444;">Yes</span></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;">Student's full name</td>
                                </tr>
                                <tr style="background: white;">
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><strong>Phone</strong></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><span style="color: #EF4444;">Yes</span></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;">10-digit phone number (for login)</td>
                                </tr>
                                <tr style="background: white;">
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><strong>Email</strong></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><span style="color: #6B7280;">No</span></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;">Email address (optional)</td>
                                </tr>
                                <tr style="background: white;">
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><strong>Password</strong></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><span style="color: #EF4444;">Yes</span></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;">Minimum 6 characters</td>
                                </tr>
                                <tr style="background: white;">
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><strong>Student Code</strong></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;"><span style="color: #6B7280;">No</span></td>
                                    <td style="padding: 12px; border: 1px solid #E5E7EB;">Auto-generated if empty</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- Preview Data -->
                <div class="table-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h2>📋 Preview Import Data</h2>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="cancel_import" class="btn btn-secondary" 
                                    onclick="return confirm('Cancel import and upload a new file?')">
                                Cancel Import
                            </button>
                        </form>
                    </div>

                    <p style="color: #6B7280; margin-bottom: 24px;">
                        Review the data below before importing. Rows with errors will be skipped.
                    </p>

                    <?php
                    $valid_count = count(array_filter($preview_data, function($item) {
                        return empty($item['errors']);
                    }));
                    $error_count = count($preview_data) - $valid_count;
                    ?>

                    <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                        <div style="padding: 16px; background: #D1FAE5; border-radius: 8px; flex: 1;">
                            <strong style="color: #065F46;">✓ <?php echo $valid_count; ?> Valid</strong>
                        </div>
                        <?php if ($error_count > 0): ?>
                            <div style="padding: 16px; background: #FEE2E2; border-radius: 8px; flex: 1;">
                                <strong style="color: #991B1B;">✗ <?php echo $error_count; ?> Errors</strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Student Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $student): ?>
                                <tr style="<?php echo !empty($student['errors']) ? 'background: #FEE2E2;' : ''; ?>">
                                    <td><?php echo $student['row']; ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_code'] ?: 'Auto'); ?></td>
                                    <td>
                                        <?php if (empty($student['errors'])): ?>
                                            <span class="status-badge active">✓ Valid</span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">✗ Error</span>
                                            <small style="display: block; color: #991B1B; margin-top: 4px;">
                                                <?php echo implode(', ', $student['errors']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($valid_count > 0): ?>
                        <form method="POST" style="margin-top: 24px;">
                            <div class="form-actions">
                                <button type="submit" name="confirm_import" class="btn btn-primary" 
                                        onclick="return confirm('Import <?php echo $valid_count; ?> students? This action cannot be undone.')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Confirm & Import <?php echo $valid_count; ?> Students
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-error" style="margin-top: 24px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            All rows have errors. Please fix your CSV file and upload again.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        // File selection handler
        document.getElementById('csvFile')?.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                document.getElementById('fileName').textContent = fileName;
                document.getElementById('fileInfo').style.display = 'block';
                document.getElementById('uploadBtn').disabled = false;
            }
        });

        // Drag and drop
        const uploadZone = document.getElementById('uploadZone');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone?.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone?.addEventListener(eventName, () => {
                uploadZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone?.addEventListener(eventName, () => {
                uploadZone.classList.remove('dragover');
            }, false);
        });

        uploadZone?.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('csvFile').files = files;
            
            const fileName = files[0]?.name;
            if (fileName) {
                document.getElementById('fileName').textContent = fileName;
                document.getElementById('fileInfo').style.display = 'block';
                document.getElementById('uploadBtn').disabled = false;
            }
        }, false);
    </script>
</body>
</html>