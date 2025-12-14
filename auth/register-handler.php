<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        header('Location: ' . SITE_URL . '/?error=missing_fields');
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: ' . SITE_URL . '/?error=password_mismatch');
        exit;
    }
    
    if (strlen($password) < 6) {
        header('Location: ' . SITE_URL . '/?error=password_short');
        exit;
    }
    
    // Check if email exists
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header('Location: ' . SITE_URL . '/?error=email_exists');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: ' . SITE_URL . '/?error=database_error');
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    try {
        $sql = "INSERT INTO users (user_type, full_name, email, phone, password, account_status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute(['student', $full_name, $email, $phone, $hashed_password, 'active']);
        
        if (!$result) {
            header('Location: ' . SITE_URL . '/?error=registration_failed');
            exit;
        }
        
        // Get new user ID
        $new_user_id = $conn->lastInsertId();
        
        // Set session
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_type'] = 'student';
        $_SESSION['user_name'] = $full_name;
        $_SESSION['last_activity'] = time();
        
        // Redirect to login
        header('Location: ' . SITE_URL . '/auth/login.php?success=registered');
        exit;
        
    } catch (PDOException $e) {
        header('Location: ' . SITE_URL . '/?error=database_error');
        exit;
    }
}

header('Location: ' . SITE_URL);
exit;
?>
