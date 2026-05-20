<?php
/**
 * POST /api/v1/customers/create
 * Create a new customer
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

// Validate required fields
$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');

if (empty($firstName) || empty($lastName)) {
    errorResponse('First name and last name are required', HTTP_BAD_REQUEST);
}

if (empty($email) && empty($phone)) {
    errorResponse('Either email or phone is required', HTTP_BAD_REQUEST);
}

$db = Database::getInstance();

// Check for duplicate email
if (!empty($email)) {
    $existing = $db->fetchOne(
        "SELECT id FROM customers WHERE email = :email",
        [':email' => $email]
    );
    
    if ($existing) {
        errorResponse('Customer with this email already exists', HTTP_CONFLICT);
    }
}

// Check for duplicate phone
if (!empty($phone)) {
    $existing = $db->fetchOne(
        "SELECT id FROM customers WHERE phone = :phone",
        [':phone' => $phone]
    );
    
    if ($existing) {
        errorResponse('Customer with this phone already exists', HTTP_CONFLICT);
    }
}

// Create customer
$customerId = $db->insert('customers', [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email ?: null,
    'phone' => $phone ?: null,
    'address' => $input['address'] ?? null,
    'city' => $input['city'] ?? null,
    'state' => $input['state'] ?? null,
    'postal_code' => $input['postal_code'] ?? null,
    'birth_date' => $input['birth_date'] ?? null,
    'points' => 0,
    'tier' => 'bronze',
    'total_spent' => 0,
    'marketing_consent' => $input['marketing_consent'] ?? false,
    'created_at' => date('Y-m-d H:i:s')
]);

// Log activity
logActivity($user['id'], 'customer_created', "Created customer: {$firstName} {$lastName}");

successResponse([
    'id' => $customerId,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone
], 'Customer created successfully');
