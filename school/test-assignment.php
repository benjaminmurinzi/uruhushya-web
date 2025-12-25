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

// Get current tab
$current_tab = $_GET['tab'] ?? 'assign';

// Get all available tests
$stmt = $conn->query("SELECT * FROM test_templates ORDER BY test_code ASC");
$available_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all school students
$stmt = $conn->prepare("
    SELECT ss.student_id, u.full_name, u.phone
    FROM school_students ss
    JOIN users u ON ss.student_id = u.id
    WHERE ss.school_id = ? AND ss.status = 'active'
    ORDER BY u.full_name ASC
");
$stmt->execute([$school_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get existing assignments
$stmt = $conn->prepare("
    SELECT 
        ta.*,
        u.full_name as student_name,
        t.test_code,
        t.name_en as test_name,
        sta.score,
        sta.passed,
        sta.created_at as completed_at
    FROM test_assignments ta
    JOIN users u ON ta.student_id = u.id
    JOIN test_templates t ON ta.test_template_id = t.id
    LEFT JOIN student_test_attempts sta ON ta.student_id = sta.student_id AND ta.test_template_id = sta.test_template_id
    WHERE ta.school_id = ?
    ORDER BY ta.created_at DESC
");
$stmt->execute([$school_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assignment statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_assignments WHERE school_id = ?");
$stmt->execute([$school_id]);
$total_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_assignments WHERE school_id = ? AND status = 'pending'");
$stmt->execute([$school_id]);
$pending_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_assignments WHERE school_id = ? AND status = 'completed'");
$stmt->execute([$school_id]);
$completed_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Assignment - URUHUSHYA School</title>
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
            
            <a href="test-assignment.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Test Assignment
                <?php if ($pending_assignments > 0): ?>
                    <span class="badge"><?php echo $pending_assignments; ?></span>
                <?php endif; ?>
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
            <h1>Test Assignment</h1>
            <div class="admin-info">
                <span>🏫 <strong><?php echo htmlspecialchars($school_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'assigned'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Test assigned successfully!
                </div>
            <?php elseif ($error === 'no_students'): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Please select at least one student
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_assignments); ?></h3>
                        <p>Total Assignments</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($pending_assignments); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($completed_assignments); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-header">
                <a href="?tab=assign" class="tab-link <?php echo $current_tab === 'assign' ? 'active' : ''; ?>">
                    Assign New Test
                </a>
                <a href="?tab=assignments" class="tab-link <?php echo $current_tab === 'assignments' ? 'active' : ''; ?>">
                    View Assignments
                    <?php if ($total_assignments > 0): ?>
                        <span class="tab-badge"><?php echo $total_assignments; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Assign New Test Tab -->
            <?php if ($current_tab === 'assign'): ?>
                <div class="form-container">
                    <h2>Assign Test to Students</h2>
                    <p style="color: #6B7280; margin-bottom: 24px;">
                        Select a test and students to create assignments
                    </p>
                    
                    <?php if (empty($students)): ?>
                        <div class="alert alert-error">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            You don't have any active students. <a href="student-add.php" style="color: #2563EB; font-weight: 600;">Add students first</a>
                        </div>
                    <?php elseif (empty($available_tests)): ?>
                        <div class="alert alert-error">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            No tests available. Please contact administrator.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="test-assign-action.php" id="assignForm">
                            
                            <div class="form-group">
                                <label>Select Test *</label>
                                <select name="test_id" id="testSelect" required>
                                    <option value="">-- Choose a test --</option>
                                    <?php foreach ($available_tests as $test): ?>
                                        <option value="<?php echo $test['id']; ?>">
                                            <?php echo htmlspecialchars($test['test_code']); ?> - 
                                            <?php echo htmlspecialchars($test['name_en']); ?>
                                            (<?php echo $test['total_questions']; ?> questions, <?php echo $test['time_limit_minutes']; ?> min)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Deadline (Optional)</label>
                                <input 
                                    type="date" 
                                    name="deadline" 
                                    min="<?php echo date('Y-m-d'); ?>"
                                >
                                <small>Leave empty for no deadline</small>
                            </div>

                            <div class="form-group">
                                <label>Notes</label>
                                <textarea 
                                    name="notes" 
                                    rows="3"
                                    placeholder="Add any instructions for students..."
                                ></textarea>
                            </div>

                            <h3 style="margin: 32px 0 16px 0; color: #1F2937;">Select Students *</h3>

                            <div style="margin-bottom: 16px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="selectAll" style="width: auto;">
                                    <strong>Select All Students</strong>
                                </label>
                            </div>

                            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px;">
                                <?php foreach ($students as $student): ?>
                                    <div style="padding: 12px; border-bottom: 1px solid #F3F4F6;">
                                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                            <input 
                                                type="checkbox" 
                                                name="student_ids[]" 
                                                value="<?php echo $student['student_id']; ?>"
                                                class="student-checkbox"
                                                style="width: auto;"
                                            >
                                            <div>
                                                <div style="font-weight: 600; color: #1F2937;">
                                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                                </div>
                                                <div style="font-size: 13px; color: #6B7280;">
                                                    <?php echo htmlspecialchars($student['phone']); ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-actions" style="margin-top: 24px;">
                                <button type="submit" class="btn btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Assign Test
                                </button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- View Assignments Tab -->
            <?php if ($current_tab === 'assignments'): ?>
                <div class="table-container">
                    <?php if (empty($assignments)): ?>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <h3>No assignments yet</h3>
                            <p>Start by assigning tests to your students</p>
                            <a href="?tab=assign" class="btn btn-primary" style="margin-top: 16px;">
                                Assign Test
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Test</th>
                                    <th>Assigned Date</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['student_name']); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['test_code']); ?></strong><br>
                                            <small style="color: #6B7280;"><?php echo htmlspecialchars($assignment['test_name']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($assignment['created_at'])); ?></td>
                                        <td>
                                            <?php if ($assignment['deadline']): ?>
                                                <?php echo date('M d, Y', strtotime($assignment['deadline'])); ?>
                                            <?php else: ?>
                                                <span style="color: #9CA3AF;">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $assignment['status']; ?>">
                                                <?php echo ucfirst($assignment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($assignment['score'] !== null): ?>
                                                <strong><?php echo $assignment['score']; ?></strong>
                                            <?php else: ?>
                                                <span style="color: #9CA3AF;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($assignment['passed'] !== null): ?>
                                                <span class="status-badge <?php echo $assignment['passed'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $assignment['passed'] ? 'Passed' : 'Failed'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #9CA3AF;">Not taken</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        // Select All functionality
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update Select All based on individual checkboxes
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll('.student-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
                document.getElementById('selectAll').checked = allCheckboxes.length === checkedCheckboxes.length;
            });
        });

        // Form validation
        document.getElementById('assignForm')?.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one student');
            }
        });
    </script>
</body>
</html>