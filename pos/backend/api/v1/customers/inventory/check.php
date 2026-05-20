<?php
/**
 * GET /api/v1/inventory/check
 * Check stock levels for products
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

$productIds = $_GET['product_ids'] ?? '';
$productIdList = $productIds ? explode(',', $productIds) : [];

$db = Database::getInstance();

if (!empty($productIdList)) {
    // Check specific products
    $placeholders = implode(',', array_fill(0, count($productIdList), '?'));
    $sql = "SELECT id, sku, name, price, stock, reorder_point FROM products WHERE id IN ({$placeholders})";
    $products = $db->fetchAll($sql, $productIdList);
} else {
    // Get all low stock products
    $sql = "SELECT id, sku, name, price, stock, reorder_point FROM products WHERE stock <= reorder_point AND reorder_point > 0";
    $products = $db->fetchAll($sql);
}

// Demo fallback
if (empty($products) && DEMO_MODE) {
    $demoProducts = $db->getDemoData('products');
    $products = array_map(function($p) {
        return [
            'id' => $p['id'],
            'sku' => $p['sku'],
            'name' => $p['name'],
            'price' => $p['price'],
            'stock' => $p['stock'],
            'reorder_point' => 10
        ];
    }, $demoProducts);
    
    if (!empty($productIdList)) {
        $products = array_filter($products, function($p) use ($productIdList) {
            return in_array($p['id'], $productIdList);
        });
        $products = array_values($products);
    }
}

// Add stock status
$formattedProducts = array_map(function($product) {
    $stock = (int)$product['stock'];
    if ($stock <= 0) {
        $status = 'out_of_stock';
    } elseif ($stock < ($product['reorder_point'] ?? 10)) {
        $status = 'low_stock';
    } else {
        $status = 'in_stock';
    }
    
    return [
        'id' => (int)$product['id'],
        'sku' => $product['sku'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'stock' => $stock,
        'stock_status' => $status,
        'reorder_point' => (int)($product['reorder_point'] ?? 10)
    ];
}, $products);

successResponse([
    'products' => $formattedProducts,
    'low_stock_count' => count(array_filter($formattedProducts, fn($p) => $p['stock_status'] === 'low_stock')),
    'out_of_stock_count' => count(array_filter($formattedProducts, fn($p) => $p['stock_status'] === 'out_of_stock'))
]);
