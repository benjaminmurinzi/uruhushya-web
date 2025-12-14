<?php
require_once '../config.php';
require_once '../includes/language.php';

// Check login
if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user = getCurrentUser();
$success = isset($_GET['success']) ? $_GET['success'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

// Get student statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_test_attempts WHERE student_id = ?");
$stmt->execute([$user['id']]);
$total_tests = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as passed FROM student_test_attempts WHERE student_id = ? AND passed = 1");
$stmt->execute([$user['id']]);
$passed_tests = $stmt->fetch()['passed'];
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'rw' ? 'Umwirondoro' : 'Profile'; ?> - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="profile-header">
        <div class="container">
            <a href="dashboard.php" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <?php echo $current_lang === 'rw' ? 'Subira' : 'Back'; ?>
            </a>
            <h1><?php echo $current_lang === 'rw' ? 'Umwirondoro Wanjye' : 'My Profile'; ?></h1>
        </div>
    </header>

    <main class="profile-main">
        <div class="container">
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $current_lang === 'rw' ? 'Amakuru yawe yavuguruwe neza!' : 'Profile updated successfully!'; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $current_lang === 'rw' ? 'Ikosa ryabaye. Ongera ugerageze.' : 'An error occurred. Please try again.'; ?>
                </div>
            <?php endif; ?>

            <div class="profile-grid">
                
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_tests; ?></div>
                            <div class="stat-label"><?php echo $current_lang === 'rw' ? 'Amasuzuma' : 'Tests'; ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $passed_tests; ?></div>
                            <div class="stat-label"><?php echo $current_lang === 'rw' ? 'Yatsinze' : 'Passed'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="form-card">
                    <h3><?php echo $current_lang === 'rw' ? 'Hindura Amakuru' : 'Edit Information'; ?></h3>
                    
                    <form method="POST" action="update-profile.php">
                        <div class="form-group">
                            <label><?php echo $current_lang === 'rw' ? 'Amazina yombi' : 'Full Name'; ?></label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo $current_lang === 'rw' ? 'Imeri' : 'Email'; ?></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo $current_lang === 'rw' ? 'Telefoni' : 'Phone'; ?></label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo $current_lang === 'rw' ? 'Bika Impinduka' : 'Save Changes'; ?>
                        </button>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="form-card">
                    <h3><?php echo $current_lang === 'rw' ? 'Hindura Ijambo ryibanga' : 'Change Password'; ?></h3>
                    
                    <form method="POST" action="change-password.php">
                        <div class="form-group">
                            <label><?php echo $current_lang === 'rw' ? 'Ijambo ryibanga rya none' : 'Current Password'; ?></label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo $current_lang === 'rw' ? 'Ijambo ryibanga rishya' : 'New Password'; ?></label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo $current_lang === 'rw' ? 'Emeza ijambo ryibanga' : 'Confirm Password'; ?></label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo $current_lang === 'rw' ? 'Hindura Ijambo ryibanga' : 'Update Password'; ?>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </main>
</body>
</html>