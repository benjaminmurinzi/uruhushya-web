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

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["ss.school_id = ?"];
$params = [$school_id];

if (!empty($status_filter)) {
    $where_conditions[] = "ss.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM school_students ss
    JOIN users u ON ss.student_id = u.id
    $where_clause
";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_students = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_students / $per_page);

// Get students
$query = "
    SELECT 
        ss.*, 
        u.full_name, 
        u.email, 
        u.phone,
        COUNT(DISTINCT sta.id) as total_tests,
        SUM(CASE WHEN sta.passed = 1 THEN 1 ELSE 0 END) as passed_tests,
        AVG(sta.score) as avg_score
    FROM school_students ss
    JOIN users u ON ss.student_id = u.id
    LEFT JOIN student_test_attempts sta ON ss.student_id = sta.student_id
    $where_clause
    GROUP BY ss.id
    ORDER BY u.full_name ASC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM school_students WHERE school_id = ? AND status = 'active'");
$stmt->execute([$school_id]);
$active_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM school_students WHERE school_id = ? AND status = 'inactive'");
$stmt->execute([$school_id]);
$inactive_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - URUHUSHYA School</title>
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
            <h1>Students Management</h1>
            <div class="admin-info">
                <span>🏫 <strong><?php echo htmlspecialchars($school_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'added'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Student added successfully!
                </div>
            <?php elseif ($success === 'updated'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Student updated successfully!
                </div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Student removed successfully!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards-grid" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($active_count); ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($inactive_count); ?></h3>
                        <p>Inactive Students</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-bar">
                <form method="GET" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by name, email, or phone..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    >
                    
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                    
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="students.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>

                <div style="display: flex; gap: 12px;">
                    <a href="student-add.php" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Student
                    </a>
                    
                    <a href="bulk-operations.php" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        Bulk Upload
                    </a>
                </div>
            </div>

            <!-- Students Table -->
            <div class="table-container">
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3>No students found</h3>
                        <p>Add your first student to get started</p>
                        <a href="student-add.php" class="btn btn-primary" style="margin-top: 16px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Student
                        </a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Code</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Tests</th>
                                <th>Passed</th>
                                <th>Avg Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['student_code']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($student['phone']); ?></div>
                                        <small style="color: #6B7280;"><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td><?php echo $student['total_tests'] ?? 0; ?></td>
                                    <td><?php echo $student['passed_tests'] ?? 0; ?></td>
                                    <td>
                                        <?php if ($student['avg_score']): ?>
                                            <strong><?php echo round($student['avg_score'], 1); ?></strong>
                                        <?php else: ?>
                                            <span style="color: #9CA3AF;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $student['status']; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="student-view.php?id=<?php echo $student['student_id']; ?>" class="btn-icon" title="View">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            
                                            <a href="student-edit.php?id=<?php echo $student['student_id']; ?>" class="btn-icon" title="Edit">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            
                                            <a href="student-delete.php?id=<?php echo $student['id']; ?>" 
                                               class="btn-icon btn-danger" 
                                               title="Remove"
                                               onclick="return confirm('Are you sure you want to remove this student?')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>