<?php
/**
 * GET /api/v1/customers/list
 * List customers with pagination and search
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

// Query parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$tier = $_GET['tier'] ?? '';

$db = Database::getInstance();

// Build query
$sql = "SELECT id, first_name, last_name, email, phone, points, tier, total_spent, last_visit_date, created_at
        FROM customers";
$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($tier) && $tier !== 'all') {
    $conditions[] = "tier = :tier";
    $params[':tier'] = $tier;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Get total count
$countSql = str_replace("SELECT id, first_name, last_name, email, phone, points, tier, total_spent, last_visit_date, created_at", 
                         "SELECT COUNT(*) as total", $sql);
$totalResult = $db->fetchOne($countSql, $params);
$total = $totalResult['total'] ?? 0;

// Add pagination
$sql .= " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
$customers = $db->fetchAll($sql, $params);

// Demo fallback
if (empty($customers) && DEMO_MODE) {
    $demoCustomers = $db->getDemoData('customers');
    $customers = array_slice($demoCustomers, $offset, $limit);
    $total = count($demoCustomers);
}

// Format response
$formattedCustomers = array_map(function($customer) {
    return [
        'id' => (int)$customer['id'],
        'name' => $customer['first_name'] . ' ' . $customer['last_name'],
        'first_name' => $customer['first_name'],
        'last_name' => $customer['last_name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'points' => (int)$customer['points'],
        'tier' => $customer['tier'],
        'total_spent' => (float)($customer['total_spent'] ?? 0),
        'last_visit' => $customer['last_visit_date'],
        'joined' => $customer['created_at']
    ];
}, $customers);

successResponse([
    'customers' => $formattedCustomers,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);
