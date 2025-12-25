<?php
/**
 * Flutterwave Payment Configuration
 * URUHUSHYA Driving School Payment Integration
 */

// =====================================================
// FLUTTERWAVE API CREDENTIALS (TEST MODE)
// =====================================================
define('FLW_PUBLIC_KEY', 'FLWPUBK_TEST-9eb8871171f12e9e6429ddecedf3db76-X');
define('FLW_SECRET_KEY', 'FLWSECK_TEST-88b354ebd77e4f1cf44ffd581bc93f51-X');
define('FLW_ENCRYPTION_KEY', 'FLWSECK_TESTfa2432a02964');

// =====================================================
// ENVIRONMENT SETTINGS
// =====================================================
define('FLW_ENVIRONMENT', 'test'); // 'test' or 'live'
define('FLW_API_URL', 'https://api.flutterwave.com/v3');

// =====================================================
// BUSINESS INFORMATION
// =====================================================
define('FLW_BUSINESS_NAME', 'Uruhushya');
define('FLW_BUSINESS_LOGO', SITE_URL . '/assets/images/logo.png');
define('FLW_CURRENCY', 'RWF'); // Rwandan Franc

// =====================================================
// WEBHOOK SETTINGS (Will configure when deploying)
// =====================================================
define('FLW_WEBHOOK_SECRET', 'webhook-secret-placeholder');
define('FLW_WEBHOOK_URL', SITE_URL . '/webhooks/flutterwave-webhook.php');

// =====================================================
// PAYMENT SETTINGS
// =====================================================
define('FLW_MIN_AMOUNT', 500);
define('FLW_MAX_AMOUNT', 10000000);

// Payment methods to enable
define('FLW_PAYMENT_OPTIONS', 'card,mobilemoneyrwanda,banktransfer');

// =====================================================
// REDIRECT URLs
// =====================================================
define('FLW_REDIRECT_URL', SITE_URL . '/student/payment-callback.php');
define('FLW_SUCCESS_URL', SITE_URL . '/student/payment-success.php');
define('FLW_CANCEL_URL', SITE_URL . '/student/payment-failed.php');

// =====================================================
// LOGGING SETTINGS
// =====================================================
define('FLW_ENABLE_LOGGING', true);

// =====================================================
// HELPER FUNCTIONS
// =====================================================
function flw_keys_configured() {
    return !empty(FLW_PUBLIC_KEY) 
        && !empty(FLW_SECRET_KEY) 
        && !empty(FLW_ENCRYPTION_KEY);
}

function flw_is_test_mode() {
    return FLW_ENVIRONMENT === 'test';
}

function flw_api_url($endpoint = '') {
    return FLW_API_URL . $endpoint;
}

function flw_format_amount($amount) {
    return number_format($amount, 0) . ' ' . FLW_CURRENCY;
}
?>