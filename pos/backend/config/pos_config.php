<?php
/**
 * POS System Configuration
 * Nile Shopping POS - Main Configuration File
 */

// ============================================
// APPLICATION SETTINGS
// ============================================

define('APP_NAME', 'Nile Shopping POS');
define('APP_VERSION', '2.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // development, staging, production
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_TIMEZONE', 'America/New_York');
date_default_timezone_set(APP_TIMEZONE);

// ============================================
// SECURITY SETTINGS
// ============================================

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'nile-pos-secret-key-change-in-production');
define('JWT_EXPIRY', 3600); // 1 hour in seconds
define('REFRESH_TOKEN_EXPIRY', 604800); // 7 days
define('BCRYPT_ROUNDS', 12);
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100); // requests per minute
define('RATE_LIMIT_LOGIN', 5); // login attempts per 15 minutes

// ============================================
// TIER CONFIGURATION
// ============================================

define('TIER_FREEMIUM', 'freemium');
define('TIER_BASIC', 'basic');
define('TIER_PROFESSIONAL', 'professional');
define('TIER_PREMIUM', 'premium');
define('TIER_ENTERPRISE', 'enterprise');

$TIER_LIMITS = [
    'freemium' => [
        'max_products' => 500,
        'max_transactions_per_month' => 100,
        'max_registers' => 1,
        'max_staff' => 2,
        'offline_mode' => false,
        'api_access' => false,
        'advanced_reports' => false,
        'loyalty_program' => false,
        'multi_store' => false
    ],
    'basic' => [
        'max_products' => 5000,
        'max_transactions_per_month' => 5000,
        'max_registers' => 2,
        'max_staff' => 5,
        'offline_mode' => true,
        'api_access' => false,
        'advanced_reports' => false,
        'loyalty_program' => false,
        'multi_store' => false
    ],
    'professional' => [
        'max_products' => 50000,
        'max_transactions_per_month' => 50000,
        'max_registers' => 5,
        'max_staff' => 20,
        'offline_mode' => true,
        'api_access' => true,
        'advanced_reports' => true,
        'loyalty_program' => true,
        'multi_store' => true
    ],
    'premium' => [
        'max_products' => 500000,
        'max_transactions_per_month' => 500000,
        'max_registers' => 999,
        'max_staff' => 100,
        'offline_mode' => true,
        'api_access' => true,
        'advanced_reports' => true,
        'loyalty_program' => true,
        'multi_store' => true
    ],
    'enterprise' => [
        'max_products' => 9999999,
        'max_transactions_per_month' => 9999999,
        'max_registers' => 9999,
        'max_staff' => 9999,
        'offline_mode' => true,
        'api_access' => true,
        'advanced_reports' => true,
        'loyalty_program' => true,
        'multi_store' => true
    ]
];

// ============================================
// TAX CONFIGURATION
// ============================================

define('DEFAULT_TAX_RATE', 10.00);
define('TAX_ROUNDING', 'nearest'); // nearest, up, down

$TAX_RATES_BY_JURISDICTION = [
    'NY' => 8.875,
    'CA' => 9.25,
    'TX' => 8.25,
    'FL' => 7.00,
    'IL' => 10.25,
    'default' => 10.00
];

// ============================================
// PAYMENT GATEWAYS
// ============================================

define('DEFAULT_PAYMENT_GATEWAY', 'stripe'); // stripe, square, adyen

$PAYMENT_GATEWAYS = [
    'stripe' => [
        'api_key' => getenv('STRIPE_API_KEY') ?: '',
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: ''
    ],
    'square' => [
        'app_id' => getenv('SQUARE_APP_ID') ?: '',
        'access_token' => getenv('SQUARE_ACCESS_TOKEN') ?: ''
    ],
    'adyen' => [
        'api_key' => getenv('ADYEN_API_KEY') ?: '',
        'merchant_account' => getenv('ADYEN_MERCHANT_ACCOUNT') ?: ''
    ]
];

// ============================================
// RECEIPT CONFIGURATION
// ============================================

define('RECEIPT_WIDTH', 58); // mm (58mm or 80mm)
define('RECEIPT_HEADER', APP_NAME);
define('RECEIPT_FOOTER', 'Thank you for shopping with us!');
define('RECEIPT_SHOW_LOGO', true);
define('RECEIPT_SHOW_QR', false);

// ============================================
// LOYALTY PROGRAM
// ============================================

define('LOYALTY_POINTS_PER_DOLLAR', 10); // 10 points per $1 spent
define('LOYALTY_POINTS_EXPIRY_DAYS', 365);
define('LOYALTY_REDEMPTION_RATE', 100); // 100 points = $1

$LOYALTY_TIERS = [
    'bronze' => ['min_points' => 0, 'discount_rate' => 1, 'color' => '#cd7f32'],
    'silver' => ['min_points' => 500, 'discount_rate' => 2, 'color' => '#c0c0c0'],
    'gold' => ['min_points' => 1000, 'discount_rate' => 3, 'color' => '#ffd700'],
    'platinum' => ['min_points' => 2500, 'discount_rate' => 5, 'color' => '#e5e4e2']
];

// ============================================
// LOGGING
// ============================================

define('LOG_PATH', dirname(__DIR__) . '/logs/');
define('LOG_LEVEL', 'info'); // debug, info, warning, error
define('LOG_RETENTION_DAYS', 30);

// ============================================
// CACHE SETTINGS
// ============================================

define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file'); // file, redis, memcached
define('CACHE_TTL', 3600); // 1 hour

// ============================================
// CORS SETTINGS
// ============================================

define('CORS_ALLOWED_ORIGINS', [
    'http://localhost:3000',
    'http://localhost:8080',
    'https://pos.nileshopping.com'
]);

define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// ============================================
// ERROR HANDLING
// ============================================

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get tier limits for a specific plan
 */
function getTierLimits($tier = TIER_FREEMIUM) {
    global $TIER_LIMITS;
    return $TIER_LIMITS[$tier] ?? $TIER_LIMITS[TIER_FREEMIUM];
}

/**
 * Check if feature is available in current tier
 */
function hasFeature($tier, $feature) {
    $limits = getTierLimits($tier);
    return $limits[$feature] ?? false;
}

/**
 * Get tax rate for location
 */
function getTaxRate($location = 'default') {
    global $TAX_RATES_BY_JURISDICTION;
    return $TAX_RATES_BY_JURISDICTION[$location] ?? $TAX_RATES_BY_JURISDICTION['default'];
}

/**
 * Format currency
 */
function formatCurrency($amount, $symbol = '$') {
    return $symbol . number_format($amount, 2);
}

/**
 * Generate unique transaction number
 */
function generateTransactionNumber() {
    return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate SKU from product name
 */
function generateSku($name, $category) {
    $prefix = substr($category, 0, 3);
    $code = substr(preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name)), 0, 6);
    return $prefix . '-' . $code . '-' . rand(100, 999);
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = null) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $logFile = LOG_PATH . 'activity_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, json_encode($log) . PHP_EOL, FILE_APPEND);
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 */
function errorResponse($message, $code = 400, $details = null) {
    $response = [
        'success' => false,
        'error' => $message,
        'code' => $code
    ];
    if ($details) {
        $response['details'] = $details;
    }
    jsonResponse($response, $code);
}

/**
 * Send success response
 */
function successResponse($data = null, $message = 'Success') {
    $response = [
        'success' => true,
        'message' => $message
    ];
    if ($data) {
        $response['data'] = $data;
    }
    jsonResponse($response, 200);
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get current user from JWT
 */
function getCurrentUser() {
    // This would extract from JWT token
    // For demo, return default user
    return [
        'id' => $_SESSION['user_id'] ?? 1,
        'email' => $_SESSION['user_email'] ?? 'admin@nile.com',
        'role' => $_SESSION['user_role'] ?? 'admin',
        'tier' => $_SESSION['user_tier'] ?? 'freemium'
    ];
}
