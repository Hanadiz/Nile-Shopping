<?php
/**
 * POST /api/v1/auth/login
 * User authentication endpoint
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/RateLimiter.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid request body', HTTP_BAD_REQUEST);
}

// Validate required fields
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$pin = $input['pin'] ?? '';
$biometric = $input['biometric'] ?? false;

if (empty($email) && empty($pin)) {
    errorResponse('Email or PIN is required', HTTP_BAD_REQUEST);
}

// Check rate limit
if (!checkLoginRateLimit($email)) {
    errorResponse('Too many login attempts. Please try again later.', HTTP_TOO_MANY_REQUESTS);
}

// Database lookup
$db = Database::getInstance();

if (!empty($pin)) {
    // PIN login (cashier fast login)
    $user = $db->fetchOne(
        "SELECT id, email, name, role, store_id, pin_code FROM users WHERE pin_code = :pin AND is_active = 1",
        [':pin' => password_hash($pin, PASSWORD_DEFAULT)] // In real, verify properly
    );
    
    // Demo PIN
    if ($pin === '1234') {
        $user = [
            'id' => 3,
            'email' => 'cashier@nile.com',
            'name' => 'Cashier User',
            'role' => 'cashier',
            'store_id' => 1
        ];
    }
} else {
    // Email/password login
    $user = $db->fetchOne(
        "SELECT id, email, name, role, store_id, password FROM users WHERE email = :email AND is_active = 1",
        [':email' => $email]
    );
    
    // Demo user check
    if (!$user && $email === 'demo@nile.com' && $password === 'demo123') {
        $user = [
            'id' => 1,
            'email' => 'demo@nile.com',
            'name' => 'Demo User',
            'role' => 'admin',
            'store_id' => 1
        ];
    }
    
    // Verify password (simplified for demo)
    if ($user && isset($user['password'])) {
        if (!password_verify($password, $user['password'])) {
            $user = null;
        }
    }
}

if (!$user) {
    // Record failed attempt
    RateLimiter::getInstance()->recordFailedLogin($email);
    errorResponse('Invalid credentials', HTTP_UNAUTHORIZED);
}

// Get user tier from store or subscription
$tier = TIER_FREEMIUM; // Default, would come from subscription table

// Generate JWT token
$auth = AuthMiddleware::getInstance();
$token = $auth->generateToken([
    'id' => $user['id'],
    'email' => $user['email'],
    'role' => $user['role'],
    'tier' => $tier,
    'store_id' => $user['store_id']
]);

// Generate refresh token
$refreshToken = bin2hex(random_bytes(32));

// Log login activity
logActivity($user['id'], 'user_login', "User logged in from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Update last login
$db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $user['id']]);

// Get user permissions
global $PERMISSIONS;
$permissions = $PERMISSIONS[$user['role']] ?? [];

// Get tier limits
$limits = getTierLimits($tier);

// Return success response
successResponse([
    'access_token' => $token,
    'refresh_token' => $refreshToken,
    'token_type' => 'Bearer',
    'expires_in' => JWT_EXPIRY,
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'store_id' => $user['store_id']
    ],
    'tier' => $tier,
    'permissions' => $permissions,
    'limits' => $limits
], 'Login successful');
