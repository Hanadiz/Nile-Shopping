#!/usr/bin/env php
<?php
/**
 * Database Seeder Script
 * Run: php seed.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pos_config.php';

echo "========================================\n";
echo "Nile POS - Database Seeder\n";
echo "========================================\n\n";

$db = Database::getInstance();

// Seed default admin user
$adminEmail = 'admin@nile.com';
$existing = $db->fetchOne("SELECT id FROM users WHERE email = :email", [':email' => $adminEmail]);

if (!$existing) {
    $db->insert('users', [
        'email' => $adminEmail,
        'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
        'name' => 'System Administrator',
        'role' => 'admin',
        'store_id' => 1,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    echo "✓ Created admin user: admin@nile.com / Admin123!\n";
} else {
    echo "✓ Admin user already exists\n";
}

// Seed demo products
$demoProducts = [
    ['sku' => 'PRD-001', 'name' => 'Classic T-Shirt', 'price' => 29.99, 'cost' => 15.00, 'stock' => 48, 'category' => 'Apparel', 'barcode' => '8901234567890'],
    ['sku' => 'PRD-002', 'name' => 'Slim Fit Jeans', 'price' => 59.99, 'cost' => 30.00, 'stock' => 35, 'category' => 'Apparel', 'barcode' => '8901234567891'],
    ['sku' => 'PRD-003', 'name' => 'Running Shoes', 'price' => 89.99, 'cost' => 45.00, 'stock' => 12, 'category' => 'Footwear', 'barcode' => '8901234567892'],
    ['sku' => 'PRD-004', 'name' => 'Leather Wallet', 'price' => 39.99, 'cost' => 20.00, 'stock' => 45, 'category' => 'Accessories', 'barcode' => '8901234567893'],
    ['sku' => 'PRD-005', 'name' => 'Smart Watch', 'price' => 199.99, 'cost' => 120.00, 'stock' => 8, 'category' => 'Electronics', 'barcode' => '8901234567894'],
];

foreach ($demoProducts as $product) {
    $existing = $db->fetchOne("SELECT id FROM products WHERE sku = :sku", [':sku' => $product['sku']]);
    
    if (!$existing) {
        $db->insert('products', array_merge($product, [
            'reorder_point' => 10,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]));
        echo "✓ Created product: {$product['name']}\n";
    }
}

echo "\n========================================\n";
echo "Seeding Complete!\n";
echo "========================================\n";
