<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$admin_name = $_SESSION['full_name'];

// Get current tab
$current_tab = $_GET['tab'] ?? 'schools';

// Get pending schools
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE user_type = 'school' AND status = 'pending' 
    ORDER BY created_at DESC
");
$stmt->execute();
$pending_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending agents
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE user_type = 'agent' AND status = 'pending' 
    ORDER BY created_at DESC
");
$stmt->execute();
$pending_agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests - URUHUSHYA Admin</title>
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
            
            <a href="questions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Questions
            </a>
            
            <a href="subscriptions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Subscriptions
            </a>
            
            <a href="transactions.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Transactions
            </a>
            
            <a href="requests.php" class="nav-item active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Requests
                <?php if ((count($pending_schools) + count($pending_agents)) > 0): ?>
                    <span class="badge"><?php echo (count($pending_schools) + count($pending_agents)); ?></span>
                <?php endif; ?>
            </a>
            
            <a href="analytics.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Analytics
            </a>
            
            <a href="settings.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m-9-9h6m6 0h6"></path>
                </svg>
                Settings
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
            <h1>Pending Requests</h1>
            <div class="admin-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </header>

        <section class="content-section">
            
            <?php if ($success === 'approved'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Request approved successfully!
                </div>
            <?php elseif ($success === 'rejected'): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Request rejected successfully!
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs-header">
                <a href="?tab=schools" class="tab-link <?php echo $current_tab === 'schools' ? 'active' : ''; ?>">
                    Schools
                    <?php if (count($pending_schools) > 0): ?>
                        <span class="tab-badge"><?php echo count($pending_schools); ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=agents" class="tab-link <?php echo $current_tab === 'agents' ? 'active' : ''; ?>">
                    Agents
                    <?php if (count($pending_agents) > 0): ?>
                        <span class="tab-badge"><?php echo count($pending_agents); ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Schools Tab -->
            <?php if ($current_tab === 'schools'): ?>
                <div class="table-container">
                    <?php if (empty($pending_schools)): ?>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            <h3>No Pending School Requests</h3>
                            <p>All school registration requests have been processed</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>School Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_schools as $school): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($school['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($school['email']); ?></td>
                                        <td><?php echo htmlspecialchars($school['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($school['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="request-view.php?id=<?php echo $school['id']; ?>" class="btn-icon" title="View Details">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                                
                                                <form method="POST" action="request-approve.php" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $school['id']; ?>">
                                                    <button type="submit" class="btn-icon btn-success" title="Approve">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" action="request-reject.php" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $school['id']; ?>">
                                                    <button type="submit" class="btn-icon btn-danger" title="Reject" 
                                                            onclick="return confirm('Are you sure you want to reject this request?')">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Agents Tab -->
            <?php if ($current_tab === 'agents'): ?>
                <div class="table-container">
                    <?php if (empty($pending_agents)): ?>
                        <div class="empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            <h3>No Pending Agent Requests</h3>
                            <p>All agent registration requests have been processed</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Agent Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_agents as $agent): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($agent['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                        <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($agent['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="request-view.php?id=<?php echo $agent['id']; ?>" class="btn-icon" title="View Details">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                                
                                                <form method="POST" action="request-approve.php" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $agent['id']; ?>">
                                                    <button type="submit" class="btn-icon btn-success" title="Approve">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" action="request-reject.php" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $agent['id']; ?>">
                                                    <button type="submit" class="btn-icon btn-danger" title="Reject"
                                                            onclick="return confirm('Are you sure you want to reject this request?')">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
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
</body>
</html>