<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['email_or_phone']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'] ?? 'student';
    
    // Check if input is email or phone
    $field = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE $field = ? AND user_type = ?");
    $stmt->execute([$email_or_phone, $user_type]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();
            
            // Redirect based on user type
            $redirect = [
                'student' => SITE_URL . '/student/dashboard.php',
                'school' => SITE_URL . '/school/dashboard.php',
                'agent' => SITE_URL . '/agent/dashboard.php'
            ];
            
            header('Location: ' . $redirect[$user_type]);
            exit;
        } else {
            header('Location: ' . SITE_URL . '/?error=inactive');
            exit;
        }
    } else {
        header('Location: ' . SITE_URL . '/?error=invalid');
        exit;
    }
} else {
    header('Location: ' . SITE_URL);
    exit;
}
?>