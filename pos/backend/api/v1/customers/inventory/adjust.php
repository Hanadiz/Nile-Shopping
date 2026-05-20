<?php
/**
 * POST /api/v1/inventory/adjust
 * Adjust inventory levels (manual count adjustment)
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication and permission
$user = requirePermission('inventory.adjust');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Invalid request body', HTTP_BAD_REQUEST);
}

$productId = $input['product_id'] ?? null;
$newQuantity = $input['new_quantity'] ?? null;
$reason = $input['reason'] ?? 'Manual adjustment';

if (!$productId || $newQuantity === null) {
    errorResponse('Product ID and new quantity are required', HTTP_BAD_REQUEST);
}

$newQuantity = (int)$newQuantity;

$db = Database::getInstance();

// Get current product
$product = $db->fetchOne(
    "SELECT id, sku, name, stock FROM products WHERE id = :id",
    [':id' => $productId]
);

if (!$product) {
    errorResponse('Product not found', HTTP_NOT_FOUND);
}

$oldQuantity = (int)$product['stock'];
$difference = $newQuantity - $oldQuantity;

// Begin transaction
$db->beginTransaction();

try {
    // Update stock
    $db->update('products',
        ['stock' => $newQuantity],
        'id = :id',
        [':id' => $productId]
    );
    
    // Record adjustment log
    $db->insert('inventory_adjustments', [
        'product_id' => $productId,
        'user_id' => $user['id'],
        'old_quantity' => $oldQuantity,
        'new_quantity' => $newQuantity,
        'difference' => $difference,
        'reason' => $reason,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Log activity
    logActivity($user['id'], 'inventory_adjusted', 
        "Adjusted {$product['name']} from {$oldQuantity} to {$newQuantity} (diff: {$difference})");
    
    $db->commit();
    
    successResponse([
        'product_id' => $productId,
        'product_name' => $product['name'],
        'old_quantity' => $oldQuantity,
        'new_quantity' => $newQuantity,
        'difference' => $difference
    ], 'Inventory adjusted successfully');
    
} catch (Exception $e) {
    $db->rollback();
    errorResponse('Adjustment failed: ' . $e->getMessage(), HTTP_INTERNAL_SERVER_ERROR);
}
