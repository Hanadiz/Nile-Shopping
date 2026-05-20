<?php
/**
 * GET /api/v1/transactions/get
 * Get transaction details by ID or transaction number
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

// Get transaction identifier
$transactionId = $_GET['id'] ?? null;
$transactionNumber = $_GET['number'] ?? null;

if (!$transactionId && !$transactionNumber) {
    errorResponse('Transaction ID or number is required', HTTP_BAD_REQUEST);
}

$db = Database::getInstance();

// Build query
$sql = "SELECT t.*, u.name as cashier_name, c.first_name, c.last_name, c.email, c.phone
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN customers c ON t.customer_id = c.id
        WHERE ";
$params = [];

if ($transactionId) {
    $sql .= "t.id = :id";
    $params[':id'] = $transactionId;
} else {
    $sql .= "t.transaction_number = :number";
    $params[':number'] = $transactionNumber;
}

// Role-based filtering
if ($user['role'] !== ROLE_ADMIN && $user['role'] !== ROLE_MANAGER) {
    $sql .= " AND t.user_id = :user_id";
    $params[':user_id'] = $user['id'];
}

$sql .= " LIMIT 1";

$transaction = $db->fetchOne($sql, $params);

if (!$transaction) {
    errorResponse('Transaction not found', HTTP_NOT_FOUND);
}

// Get transaction items
$items = $db->fetchAll(
    "SELECT ti.*, p.name, p.sku, p.category 
     FROM transaction_items ti
     JOIN products p ON ti.product_id = p.id
     WHERE ti.transaction_id = :transaction_id",
    [':transaction_id' => $transaction['id']]
);

// Get payments
$payments = $db->fetchAll(
    "SELECT * FROM payments WHERE transaction_id = :transaction_id",
    [':transaction_id' => $transaction['id']]
);

// Format response
$response = [
    'id' => (int)$transaction['id'],
    'transaction_number' => $transaction['transaction_number'],
    'date' => $transaction['created_at'],
    'status' => $transaction['status'],
    'subtotal' => (float)$transaction['subtotal'],
    'tax' => (float)$transaction['tax'],
    'total' => (float)$transaction['total'],
    'cashier' => [
        'id' => (int)$transaction['user_id'],
        'name' => $transaction['cashier_name']
    ],
    'customer' => $transaction['customer_id'] ? [
        'id' => (int)$transaction['customer_id'],
        'name' => trim($transaction['first_name'] . ' ' . $transaction['last_name']),
        'email' => $transaction['email'],
        'phone' => $transaction['phone']
    ] : null,
    'items' => array_map(function($item) {
        return [
            'id' => (int)$item['id'],
            'product_id' => (int)$item['product_id'],
            'name' => $item['name'],
            'sku' => $item['sku'],
            'quantity' => (int)$item['quantity'],
            'unit_price' => (float)$item['unit_price'],
            'discount' => (float)$item['discount'],
            'line_total' => (float)$item['line_total']
        ];
    }, $items),
    'payments' => array_map(function($payment) {
        return [
            'type' => $payment['type'],
            'amount' => (float)$payment['amount'],
            'reference' => $payment['reference'],
            'status' => $payment['status']
        ];
    }, $payments)
];

successResponse($response);
