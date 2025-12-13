<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'] ?? 'student';
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'name_required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'email_invalid';
    }
    
    if (empty($phone)) {
        $errors[] = 'phone_required';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'password_short';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'password_mismatch';
    }
    
    // Check if email exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'email_exists';
        }
    }
    
    if (!empty($errors)) {
        header('Location: ' . SITE_URL . '/?error=' . implode(',', $errors));
        exit;
    }
    
    // Register user
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (user_type, full_name, email, phone, password, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
        
        if ($stmt->execute([$user_type, $full_name, $email, $phone, $hashed_password])) {
            // Auto login
            $user_id = $conn->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['last_activity'] = time();
            
            header('Location: ' . SITE_URL . '/student/dashboard.php?welcome=1');
            exit;
        } else {
            header('Location: ' . SITE_URL . '/?error=registration_failed');
            exit;
        }
    } catch (Exception $e) {
        header('Location: ' . SITE_URL . '/?error=database_error');
        exit;
    }
} else {
    header('Location: ' . SITE_URL);
    exit;
}
?>