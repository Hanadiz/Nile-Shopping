<?php
/**
 * POST /api/v1/auth/logout
 * User logout endpoint
 */

require_once '../../../config/pos_config.php';
require_once '../../../middleware/AuthMiddleware.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

// Get refresh token from request
$input = json_decode(file_get_contents('php://input'), true);
$refreshToken = $input['refresh_token'] ?? null;

$db = Database::getInstance();

// Invalidate refresh token (if stored)
if ($refreshToken) {
    // Delete token from database
    // $db->delete('refresh_tokens', 'token = :token', [':token' => $refreshToken]);
}

// Log logout activity
logActivity($user['id'], 'user_logout', "User logged out");

successResponse(null, 'Logged out successfully');
