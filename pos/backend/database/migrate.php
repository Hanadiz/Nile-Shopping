#!/usr/bin/env php
<?php
/**
 * Database Migration Script
 * Run: php migrate.php
 */

require_once __DIR__ . '/../config/database.php';

echo "========================================\n";
echo "Nile POS - Database Migration Tool\n";
echo "========================================\n\n";

$db = Database::getInstance();

// Check if migration table exists
$db->query("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get executed migrations
$executed = $db->fetchAll("SELECT migration FROM migrations");
$executedList = array_column($executed, 'migration');

// Migration files
$migrations = [
    'V1__create_users_table' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            role VARCHAR(50) DEFAULT 'cashier',
            store_id INT,
            pin_code VARCHAR(10),
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    
    'V2__create_products_table' => "
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sku VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(200) NOT NULL,
            price DECIMAL(12,2) NOT NULL,
            cost DECIMAL(12,2) DEFAULT 0,
            stock INT DEFAULT 0,
            category VARCHAR(50),
            barcode VARCHAR(50),
            reorder_point INT DEFAULT 10,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    
    'V3__create_customers_table' => "
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            points INT DEFAULT 0,
            tier VARCHAR(20) DEFAULT 'bronze',
            total_spent DECIMAL(12,2) DEFAULT 0,
            marketing_consent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    
    'V4__create_transactions_table' => "
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_number VARCHAR(50) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            customer_id INT,
            store_id INT,
            subtotal DECIMAL(12,2) NOT NULL,
            tax DECIMAL(12,2) DEFAULT 0,
            total DECIMAL(12,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            payment_method VARCHAR(50),
            voided_by INT,
            void_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ",
    
    'V5__create_transaction_items_table' => "
        CREATE TABLE IF NOT EXISTS transaction_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(12,2) NOT NULL,
            discount DECIMAL(12,2) DEFAULT 0,
            line_total DECIMAL(12,2) NOT NULL,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ",
    
    'V6__create_payments_table' => "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            reference VARCHAR(100),
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id)
        )
    ",
    
    'V7__create_inventory_adjustments_table' => "
        CREATE TABLE IF NOT EXISTS inventory_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            old_quantity INT NOT NULL,
            new_quantity INT NOT NULL,
            difference INT NOT NULL,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ",
    
    'V8__create_refunds_table' => "
        CREATE TABLE IF NOT EXISTS refunds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            tax DECIMAL(12,2) DEFAULT 0,
            reason TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ",
    
    'V9__create_refund_items_table' => "
        CREATE TABLE IF NOT EXISTS refund_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            refund_id INT NOT NULL,
            transaction_item_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            FOREIGN KEY (refund_id) REFERENCES refunds(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    "
];

$batch = 1;
$executedCount = 0;

foreach ($migrations as $name => $sql) {
    if (in_array($name, $executedList)) {
        echo "✓ Skipping {$name} (already executed)\n";
        continue;
    }
    
    echo "→ Running {$name}... ";
    
    try {
        $db->query($sql);
        $db->insert('migrations', [
            'migration' => $name,
            'batch' => $batch
        ]);
        echo "OK\n";
        $executedCount++;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "Migration Complete!\n";
echo "Executed: {$executedCount} new migrations\n";
echo "========================================\n";
