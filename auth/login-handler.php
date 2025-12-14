<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$email_or_phone = trim($_POST['email_or_phone'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($email_or_phone) || empty($password)) {
    header('Location: ' . SITE_URL . '/index.php?error=empty_fields');
    exit;
}

try {
    // Determine if input is email or phone
    $field = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    
    // Prepare and execute query (PDO syntax)
    $stmt = $conn->prepare("SELECT * FROM users WHERE $field = ? LIMIT 1");
    $stmt->execute([$email_or_phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: ' . SITE_URL . '/index.php?error=invalid_credentials');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        header('Location: ' . SITE_URL . '/index.php?error=invalid_credentials');
        exit;
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        header('Location: ' . SITE_URL . '/index.php?error=account_inactive');
        exit;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    
    // Redirect based on user type
    switch ($user['user_type']) {
        case 'admin':
            header('Location: ' . SITE_URL . '/admin/dashboard.php');
            break;
            
        case 'student':
            header('Location: ' . SITE_URL . '/student/dashboard.php');
            break;
            
        case 'school':
            header('Location: ' . SITE_URL . '/school/dashboard.php');
            break;
            
        case 'agent':
            header('Location: ' . SITE_URL . '/agent/dashboard.php');
            break;
            
        default:
            // Unknown user type
            session_destroy();
            header('Location: ' . SITE_URL . '/index.php?error=invalid_user_type');
            break;
    }
    
    exit;
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    header('Location: ' . SITE_URL . '/index.php?error=system_error');
    exit;
}
?>