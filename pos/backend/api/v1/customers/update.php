<?php
/**
 * PUT /api/v1/customers/update
 * Update customer information
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid request body', HTTP_BAD_REQUEST);
}

$customerId = $input['id'] ?? null;

if (!$customerId) {
    errorResponse('Customer ID is required', HTTP_BAD_REQUEST);
}

$db = Database::getInstance();

// Check if customer exists
$existing = $db->fetchOne(
    "SELECT id FROM customers WHERE id = :id",
    [':id' => $customerId]
);

if (!$existing) {
    errorResponse('Customer not found', HTTP_NOT_FOUND);
}

// Build update data
$updateData = [];
$allowedFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'postal_code', 'birth_date', 'marketing_consent'];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        $updateData[$field] = $input[$field];
    }
}

if (empty($updateData)) {
    errorResponse('No fields to update', HTTP_BAD_REQUEST);
}

// Check for duplicate email (if changing)
if (isset($updateData['email']) && !empty($updateData['email'])) {
    $duplicate = $db->fetchOne(
        "SELECT id FROM customers WHERE email = :email AND id != :id",
        [':email' => $updateData['email'], ':id' => $customerId]
    );
    
    if ($duplicate) {
        errorResponse('Another customer already uses this email', HTTP_CONFLICT);
    }
}

// Check for duplicate phone
if (isset($updateData['phone']) && !empty($updateData['phone'])) {
    $duplicate = $db->fetchOne(
        "SELECT id FROM customers WHERE phone = :phone AND id != :id",
        [':phone' => $updateData['phone'], ':id' => $customerId]
    );
    
    if ($duplicate) {
        errorResponse('Another customer already uses this phone', HTTP_CONFLICT);
    }
}

// Update customer
$db->update('customers', $updateData, 'id = :id', [':id' => $customerId]);

// Log activity
logActivity($user['id'], 'customer_updated', "Updated customer ID: {$customerId}");

successResponse(null, 'Customer updated successfully');
