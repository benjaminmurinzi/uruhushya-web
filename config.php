<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uruhushya');

// Site Configuration
define('SITE_URL', 'http://localhost/uruhushya-web');
define('SITE_NAME', 'URUHUSHYA');
define('SITE_EMAIL', 'info@uruhushya.com');
define('SITE_PHONE', '+250 791 569 555');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');

// Pricing
define('PRICE_1_DAY', 1000);
define('PRICE_1_WEEK', 4900);
define('PRICE_1_MONTH', 9900);

// Session timeout (2 hours)
define('SESSION_TIMEOUT', 7200);

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getDashboardUrl($user_type) {
    $urls = [
        'student' => SITE_URL . '/student/dashboard.php',
        'school' => SITE_URL . '/school/dashboard.php',
        'agent' => SITE_URL . '/agent/dashboard.php'
    ];
    return $urls[$user_type] ?? SITE_URL;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/student/login.php');
        exit;
    }
}

// Database Connection
try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>