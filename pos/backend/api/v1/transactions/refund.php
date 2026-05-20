<?php
/**
 * POST /api/v1/transactions/refund
 * Process refund for a transaction
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication and permission
$user = requirePermission('transaction.refund');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid request body', HTTP_BAD_REQUEST);
}

$transactionId = $input['transaction_id'] ?? null;
$items = $input['items'] ?? []; // Array of items to refund
$fullRefund = $input['full_refund'] ?? false;

if (!$transactionId) {
    errorResponse('Transaction ID is required', HTTP_BAD_REQUEST);
}

if (empty($items) && !$fullRefund) {
    errorResponse('Items to refund are required', HTTP_BAD_REQUEST);
}

$db = Database::getInstance();

// Get original transaction
$transaction = $db->fetchOne(
    "SELECT * FROM transactions WHERE id = :id",
    [':id' => $transactionId]
);

if (!$transaction) {
    errorResponse('Transaction not found', HTTP_NOT_FOUND);
}

if ($transaction['status'] === TRANSACTION_REFUNDED) {
    errorResponse('Transaction already fully refunded', HTTP_CONFLICT);
}

// Get original items
$originalItems = $db->fetchAll(
    "SELECT ti.*, p.name, p.sku, p.stock 
     FROM transaction_items ti
     JOIN products p ON ti.product_id = p.id
     WHERE ti.transaction_id = :transaction_id",
    [':transaction_id' => $transactionId]
);

$refundTotal = 0;
$refundItems = [];

if ($fullRefund) {
    // Refund all items
    foreach ($originalItems as $item) {
        $refundItems[] = $item;
        $refundTotal += $item['line_total'];
    }
} else {
    // Refund selected items
    foreach ($items as $refundItem) {
        $originalItem = null;
        foreach ($originalItems as $oi) {
            if ($oi['id'] == $refundItem['item_id']) {
                $originalItem = $oi;
                break;
            }
        }
        
        if (!$originalItem) {
            errorResponse("Item {$refundItem['item_id']} not found in transaction", HTTP_NOT_FOUND);
        }
        
        $refundQuantity = min($refundItem['quantity'], $originalItem['quantity']);
        $refundAmount = ($originalItem['unit_price'] * $refundQuantity) - ($originalItem['discount'] * ($refundQuantity / $originalItem['quantity']));
        
        $refundItems[] = [
            'id' => $originalItem['id'],
            'product_id' => $originalItem['product_id'],
            'name' => $originalItem['name'],
            'sku' => $originalItem['sku'],
            'quantity' => $refundQuantity,
            'unit_price' => $originalItem['unit_price'],
            'line_total' => $refundAmount
        ];
        $refundTotal += $refundAmount;
    }
}

// Calculate refund tax (proportional)
$refundTax = ($refundTotal / $transaction['subtotal']) * $transaction['tax'];
$refundGrandTotal = $refundTotal + $refundTax;

// Begin transaction
$db->beginTransaction();

try {
    // Create refund record
    $refundId = $db->insert('refunds', [
        'transaction_id' => $transactionId,
        'user_id' => $user['id'],
        'amount' => $refundGrandTotal,
        'tax' => $refundTax,
        'reason' => $input['reason'] ?? 'Customer request',
        'status' => 'completed',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Create refund items
    foreach ($refundItems as $item) {
        $db->insert('refund_items', [
            'refund_id' => $refundId,
            'transaction_item_id' => $item['id'],
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'amount' => $item['line_total']
        ]);
        
        // Restore stock
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
    
    // Update transaction status
    $newStatus = $fullRefund ? TRANSACTION_REFUNDED : TRANSACTION_PARTIALLY_REFUNDED;
    $db->update('transactions',
        ['status' => $newStatus],
        'id = :id',
        [':id' => $transactionId]
    );
    
    // Log activity
    logActivity($user['id'], 'refund_processed', 
        "Refunded $". number_format($refundGrandTotal, 2) . " for transaction {$transaction['transaction_number']}");
    
    $db->commit();
    
    successResponse([
        'refund_id' => $refundId,
        'transaction_id' => $transactionId,
        'transaction_number' => $transaction['transaction_number'],
        'refund_amount' => $refundGrandTotal,
        'items_refunded' => count($refundItems)
    ], 'Refund processed successfully');
    
} catch (Exception $e) {
    $db->rollback();
    errorResponse('Refund failed: ' . $e->getMessage(), HTTP_INTERNAL_SERVER_ERROR);
}
