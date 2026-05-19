<?php
/**
 * GET /api/v1/auth/verify
 * Verify token validity and return user info
 */

require_once '../../../config/pos_config.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

// Get updated permissions
global $PERMISSIONS;
$permissions = $PERMISSIONS[$user['role']] ?? [];

successResponse([
    'user' => $user,
    'permissions' => $permissions,
    'authenticated' => true
], 'Token valid');
