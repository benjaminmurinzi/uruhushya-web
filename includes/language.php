<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current language
function getCurrentLanguage() {
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    if (isset($_COOKIE['language'])) {
        return $_COOKIE['language'];
    }
    return 'rw'; // Default to Kinyarwanda
}

// Set language
function setLanguage($lang) {
    if (in_array($lang, ['rw', 'en'])) {
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
        return true;
    }
    return false;
}

// Handle language switching
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect_url");
    exit;
}

$current_lang = getCurrentLanguage();

// Simple translation function
function t($key) {
    global $current_lang;
    
    $translations = [
        'rw' => [
            'login' => 'Injira',
            'register' => 'Iyandikishe',
            'hero_title' => 'Ukeneye uruhushya rwo gutwara?',
            'tests' => 'Isuzumabumenyi',
            'lessons' => 'Amasomo',
            'pricing' => 'Ibiciro',
            'start' => 'Tangira',
            'subscribe' => 'Iyandikishe',
            '1_day' => '1 Umunsi',
            '1_week' => '1 Icyumweru',
            '1_month' => '1 Ukwezi',
        ],
        'en' => [
            'login' => 'Login',
            'register' => 'Register',
            'hero_title' => 'Need a driving license?',
            'tests' => 'Tests',
            'lessons' => 'Lessons',
            'pricing' => 'Pricing',
            'start' => 'Start',
            'subscribe' => 'Subscribe',
            '1_day' => '1 Day',
            '1_week' => '1 Week',
            '1_month' => '1 Month',
        ]
    ];
    
    return $translations[$current_lang][$key] ?? $key;
}
?>