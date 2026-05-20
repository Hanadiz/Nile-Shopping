<?php
/**
 * POST /api/v1/transactions/void
 * Void a transaction (before completion or within grace period)
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication and permission
$user = requirePermission('transaction.void');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid request body', HTTP_BAD_REQUEST);
}

$transactionId = $input['transaction_id'] ?? null;
$reason = $input['reason'] ?? 'Voided by cashier';

if (!$transactionId) {
    errorResponse('Transaction ID is required', HTTP_BAD_REQUEST);
}

$db = Database::getInstance();

// Get transaction
$transaction = $db->fetchOne(
    "SELECT * FROM transactions WHERE id = :id",
    [':id' => $transactionId]
);

if (!$transaction) {
    errorResponse('Transaction not found', HTTP_NOT_FOUND);
}

// Check if voidable (pending or within 1 hour for completed)
if ($transaction['status'] === TRANSACTION_COMPLETED) {
    $transactionTime = strtotime($transaction['created_at']);
    $currentTime = time();
    $hoursDiff = ($currentTime - $transactionTime) / 3600;
    
    if ($hoursDiff > 1) {
        errorResponse('Transactions older than 1 hour cannot be voided. Please process a refund instead.', HTTP_UNPROCESSABLE_ENTITY);
    }
}

if ($transaction['status'] === TRANSACTION_VOIDED) {
    errorResponse('Transaction already voided', HTTP_CONFLICT);
}

if ($transaction['status'] === TRANSACTION_REFUNDED) {
    errorResponse('Refunded transactions cannot be voided', HTTP_CONFLICT);
}

// Get transaction items to restore stock
$items = $db->fetchAll(
    "SELECT * FROM transaction_items WHERE transaction_id = :transaction_id",
    [':transaction_id' => $transactionId]
);

// Begin transaction
$db->beginTransaction();

try {
    // Update transaction status
    $db->update('transactions', [
        'status' => TRANSACTION_VOIDED,
        'voided_by' => $user['id'],
        'voided_at' => date('Y-m-d H:i:s'),
        'void_reason' => $reason
    ], 'id = :id', [':id' => $transactionId]);
    
    // Restore stock for each item
    foreach ($items as $item) {
        $currentStock = $db->fetchOne(
            "SELECT stock FROM products WHERE id = :id",
            [':id' => $item['product_id']]
        );
        
        $db->update('products',
            ['stock' => $currentStock['stock'] + $item['quantity']],
            'id = :id',
            [':id' => $item['product_id']]
        );
    }
    
    // Void any pending payments
    $db->update('payments',
        ['status' => 'voided'],
        'transaction_id = :transaction_id AND status = "pending"',
        [':transaction_id' => $transactionId]
    );
    
    // Log activity
    logActivity($user['id'], 'transaction_voided', 
        "Voided transaction {$transaction['transaction_number']}. Reason: {$reason}");
    
    $db->commit();
    
    successResponse([
        'transaction_id' => $transactionId,
        'transaction_number' => $transaction['transaction_number'],
        'status' => TRANSACTION_VOIDED,
        'voided_by' => $user['email'],
        'voided_at' => date('Y-m-d H:i:s')
    ], 'Transaction voided successfully');
    
} catch (Exception $e) {
    $db->rollback();
    errorResponse('Void failed: ' . $e->getMessage(), HTTP_INTERNAL_SERVER_ERROR);
}
