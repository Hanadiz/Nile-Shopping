-- =====================================================
-- Nile Shopping POS - Complete Database Schema
-- Database: nile-shopping-db
-- Version: 3.0
-- =====================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `nile-shopping-db` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `nile-shopping-db`;

-- =====================================================
-- STORES TABLE (Multi-store support)
-- =====================================================
CREATE TABLE IF NOT EXISTS `stores` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL DEFAULT (UUID()),
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `address` TEXT,
    `city` VARCHAR(50),
    `state` VARCHAR(20),
    `postal_code` VARCHAR(20),
    `country` VARCHAR(50) DEFAULT 'US',
    `phone` VARCHAR(20),
    `email` VARCHAR(100),
    `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
    `timezone` VARCHAR(50) DEFAULT 'America/New_York',
    `currency` CHAR(3) DEFAULT 'USD',
    `currency_symbol` VARCHAR(5) DEFAULT '$',
    `receipt_header` TEXT,
    `receipt_footer` TEXT,
    `logo_url` VARCHAR(255),
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USERS TABLE (Employees with roles)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL DEFAULT (UUID()),
    `store_id` BIGINT UNSIGNED,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `employee_code` VARCHAR(20) UNIQUE,
    `role` ENUM('admin', 'manager', 'supervisor', 'cashier', 'stock_clerk', 'viewer') NOT NULL DEFAULT 'cashier',
    `pin_code` VARCHAR(255),
    `biometric_hash` VARCHAR(255),
    `hourly_rate` DECIMAL(10,2),
    `commission_rate` DECIMAL(5,2) DEFAULT 0.00,
    `max_discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    `can_void` BOOLEAN DEFAULT FALSE,
    `can_refund` BOOLEAN DEFAULT FALSE,
    `can_no_sale` BOOLEAN DEFAULT FALSE,
    `max_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45),
    `is_active` BOOLEAN DEFAULT TRUE,
    `requires_password_change` BOOLEAN DEFAULT FALSE,
    `mfa_enabled` BOOLEAN DEFAULT FALSE,
    `mfa_secret` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE SET NULL,
    INDEX `idx_store` (`store_id`),
    INDEX `idx_role` (`role`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REGISTERS TABLE (POS terminals)
-- =====================================================
CREATE TABLE IF NOT EXISTS `registers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `register_number` VARCHAR(20) NOT NULL,
    `name` VARCHAR(50),
    `hardware_id` VARCHAR(100) UNIQUE,
    `ip_address` VARCHAR(45),
    `status` ENUM('offline', 'online', 'closed', 'maintenance') DEFAULT 'offline',
    `last_heartbeat` TIMESTAMP NULL,
    `current_session_id` BIGINT UNSIGNED NULL,
    `firmware_version` VARCHAR(20),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
    INDEX `idx_store` (`store_id`),
    INDEX `idx_status` (`status`),
    UNIQUE KEY `uk_store_register` (`store_id`, `register_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REGISTER SESSIONS (Cashier shifts)
-- =====================================================
CREATE TABLE IF NOT EXISTS `register_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `register_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `opened_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `closed_at` TIMESTAMP NULL,
    `opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `closing_balance` DECIMAL(12,2),
    `cash_sales` DECIMAL(12,2) DEFAULT 0.00,
    `card_sales` DECIMAL(12,2) DEFAULT 0.00,
    `gift_card_sales` DECIMAL(12,2) DEFAULT 0.00,
    `refunds_total` DECIMAL(12,2) DEFAULT 0.00,
    `no_sale_count` INT DEFAULT 0,
    `expected_cash` DECIMAL(12,2),
    `actual_cash` DECIMAL(12,2),
    `cash_difference` DECIMAL(12,2),
    `notes` TEXT,
    `status` ENUM('open', 'closed', 'suspended') DEFAULT 'open',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`register_id`) REFERENCES `registers`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_register` (`register_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_opened` (`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CATEGORIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT,
    `image_url` VARCHAR(255),
    `sort_order` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUPPLIERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `contact_name` VARCHAR(100),
    `email` VARCHAR(100),
    `phone` VARCHAR(20),
    `address` TEXT,
    `tax_id` VARCHAR(50),
    `lead_time_days` INT DEFAULT 7,
    `payment_terms` VARCHAR(50) DEFAULT 'Net 30',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PRODUCTS TABLE (with matrix support)
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sku` VARCHAR(50) UNIQUE NOT NULL,
    `upc` VARCHAR(50),
    `isbn` VARCHAR(20),
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `category_id` BIGINT UNSIGNED,
    `supplier_id` BIGINT UNSIGNED,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `cost_price` DECIMAL(12,2) NOT NULL,
    `wholesale_price` DECIMAL(12,2),
    `tax_class` ENUM('standard', 'reduced', 'zero', 'exempt') DEFAULT 'standard',
    `weight` DECIMAL(10,3),
    `dimensions` VARCHAR(50),
    `brand` VARCHAR(100),
    `season` VARCHAR(20),
    `is_taxable` BOOLEAN DEFAULT TRUE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_serialized` BOOLEAN DEFAULT FALSE,
    `reorder_point` INT DEFAULT 0,
    `reorder_quantity` INT DEFAULT 0,
    `image_url` VARCHAR(255),
    `images` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    INDEX `idx_sku` (`sku`),
    INDEX `idx_upc` (`upc`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_active` (`is_active`),
    FULLTEXT `idx_search` (`name`, `description`, `sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PRODUCT ATTRIBUTES (Size, Color, etc.)
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_attributes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `attribute_name` VARCHAR(50) NOT NULL,
    `attribute_value` VARCHAR(100) NOT NULL,
    `additional_price` DECIMAL(10,2) DEFAULT 0.00,
    `sku_suffix` VARCHAR(20),
    `stock_quantity` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product` (`product_id`),
    UNIQUE KEY `uk_product_attribute` (`product_id`, `attribute_name`, `attribute_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STOCK LEVELS (Multi-location inventory)
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_levels` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `quantity_on_hand` INT NOT NULL DEFAULT 0,
    `quantity_allocated` INT NOT NULL DEFAULT 0,
    `quantity_on_order` INT NOT NULL DEFAULT 0,
    `reorder_point` INT DEFAULT 0,
    `reorder_quantity` INT DEFAULT 0,
    `last_count_date` DATE,
    `last_count_quantity` INT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`),
    UNIQUE KEY `uk_product_store` (`product_id`, `store_id`),
    INDEX `idx_product` (`product_id`),
    INDEX `idx_store` (`store_id`),
    INDEX `idx_low_stock` (`quantity_on_hand`, `reorder_point`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CUSTOMERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `customers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL DEFAULT (UUID()),
    `store_id` BIGINT UNSIGNED,
    `email` VARCHAR(100) UNIQUE,
    `phone` VARCHAR(20) UNIQUE,
    `first_name` VARCHAR(50),
    `last_name` VARCHAR(50),
    `loyalty_tier` ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    `loyalty_points` INT DEFAULT 0,
    `lifetime_value` DECIMAL(12,2) DEFAULT 0.00,
    `total_visits` INT DEFAULT 0,
    `last_visit_date` DATE,
    `birth_date` DATE,
    `tax_exempt` BOOLEAN DEFAULT FALSE,
    `tax_exempt_number` VARCHAR(50),
    `marketing_consent` BOOLEAN DEFAULT FALSE,
    `email_verified` BOOLEAN DEFAULT FALSE,
    `phone_verified` BOOLEAN DEFAULT FALSE,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_loyalty` (`loyalty_tier`),
    INDEX `idx_last_visit` (`last_visit_date`),
    FULLTEXT `idx_name` (`first_name`, `last_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CUSTOMER ADDRESSES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `customer_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('shipping', 'billing', 'both') DEFAULT 'shipping',
    `address` TEXT NOT NULL,
    `city` VARCHAR(50),
    `state` VARCHAR(20),
    `postal_code` VARCHAR(20),
    `country` VARCHAR(50) DEFAULT 'US',
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    INDEX `idx_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOYALTY TRANSACTIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `transaction_id` BIGINT UNSIGNED,
    `points_earned` INT DEFAULT 0,
    `points_redeemed` INT DEFAULT 0,
    `reason` VARCHAR(100),
    `expires_at` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- GIFT CARDS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `gift_cards` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `card_number` VARCHAR(50) UNIQUE NOT NULL,
    `pin_hash` VARCHAR(255) NOT NULL,
    `initial_balance` DECIMAL(12,2) NOT NULL,
    `current_balance` DECIMAL(12,2) NOT NULL,
    `customer_id` BIGINT UNSIGNED NULL,
    `purchased_by` VARCHAR(100),
    `recipient_name` VARCHAR(100),
    `recipient_email` VARCHAR(100),
    `message` TEXT,
    `expires_at` DATE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    INDEX `idx_card_number` (`card_number`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRANSACTIONS TABLE (Partitioned by date)
-- =====================================================
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) UNIQUE NOT NULL,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `register_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `transaction_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_total` DECIMAL(12,2) DEFAULT 0.00,
    `tax_total` DECIMAL(12,2) DEFAULT 0.00,
    `shipping_total` DECIMAL(12,2) DEFAULT 0.00,
    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount_tendered` DECIMAL(12,2),
    `change_due` DECIMAL(12,2),
    `payment_method` VARCHAR(50),
    `payment_reference` VARCHAR(100),
    `status` ENUM('pending', 'completed', 'voided', 'refunded', 'partially_refunded') DEFAULT 'pending',
    `void_reason` VARCHAR(255),
    `voided_by` BIGINT UNSIGNED NULL,
    `voided_at` TIMESTAMP NULL,
    `is_offline` BOOLEAN DEFAULT FALSE,
    `sync_id` CHAR(36) NULL,
    `customer_notes` TEXT,
    `internal_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`),
    FOREIGN KEY (`register_id`) REFERENCES `registers`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
    FOREIGN KEY (`session_id`) REFERENCES `register_sessions`(`id`),
    INDEX `idx_transaction_number` (`transaction_number`),
    INDEX `idx_date` (`transaction_date`),
    INDEX `idx_store_date` (`store_id`, `transaction_date`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_session` (`session_id`),
    INDEX `idx_sync` (`sync_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRANSACTION ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `transaction_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `attribute_id` BIGINT UNSIGNED NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
    `discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
    `line_total` DECIMAL(12,2) NOT NULL,
    `serial_number` VARCHAR(50) NULL,
    `cost_at_sale` DECIMAL(12,2),
    `is_returned` BOOLEAN DEFAULT FALSE,
    `returned_quantity` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    INDEX `idx_transaction` (`transaction_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAYMENTS TABLE (Split payments support)
-- =====================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `payment_type` ENUM('cash', 'card', 'gift_card', 'store_credit', 'check', 'mobile_payment', 'bnpl') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `reference_number` VARCHAR(100),
    `card_last4` CHAR(4),
    `card_type` VARCHAR(20),
    `auth_code` VARCHAR(50),
    `gateway_response` TEXT,
    `status` ENUM('pending', 'approved', 'declined', 'refunded') DEFAULT 'pending',
    `processed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    INDEX `idx_transaction` (`transaction_id`),
    INDEX `idx_reference` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REFUNDS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `refunds` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `refund_number` VARCHAR(50) UNIQUE NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `tax` DECIMAL(12,2) DEFAULT 0.00,
    `reason` TEXT,
    `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_transaction` (`transaction_id`),
    INDEX `idx_refund_number` (`refund_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REFUND ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `refund_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `refund_id` BIGINT UNSIGNED NOT NULL,
    `transaction_item_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`refund_id`) REFERENCES `refunds`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`transaction_item_id`) REFERENCES `transaction_items`(`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PURCHASE ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) UNIQUE NOT NULL,
    `supplier_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_date` DATE,
    `received_date` DATE,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `tax` DECIMAL(12,2) DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL,
    `status` ENUM('draft', 'sent', 'partially_received', 'received', 'cancelled') DEFAULT 'draft',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`),
    INDEX `idx_po_number` (`po_number`),
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PURCHASE ORDER ITEMS
-- =====================================================
CREATE TABLE IF NOT EXISTS `po_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity_ordered` INT NOT NULL,
    `quantity_received` INT DEFAULT 0,
    `unit_cost` DECIMAL(12,2) NOT NULL,
    `line_total` DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    INDEX `idx_po` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INVENTORY ADJUSTMENTS (Audit trail)
-- =====================================================
CREATE TABLE IF NOT EXISTS `inventory_adjustments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `old_quantity` INT NOT NULL,
    `new_quantity` INT NOT NULL,
    `difference` INT NOT NULL,
    `reason` VARCHAR(255),
    `adjustment_type` ENUM('manual', 'cycle_count', 'return', 'damage', 'theft') DEFAULT 'manual',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_product` (`product_id`),
    INDEX `idx_store` (`store_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOG (Security and compliance)
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED,
    `store_id` BIGINT UNSIGNED,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50),
    `entity_id` VARCHAR(100),
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- OFFLINE SYNC QUEUE
-- =====================================================
CREATE TABLE IF NOT EXISTS `offline_sync_queue` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `register_id` BIGINT UNSIGNED NOT NULL,
    `sync_id` CHAR(36) NOT NULL,
    `operation` ENUM('insert', 'update', 'delete') NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_data` JSON NOT NULL,
    `retry_count` INT DEFAULT 0,
    `last_attempt` TIMESTAMP NULL,
    `synced_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`register_id`) REFERENCES `registers`(`id`),
    INDEX `idx_register` (`register_id`),
    INDEX `idx_sync_id` (`sync_id`),
    INDEX `idx_pending` (`synced_at`, `retry_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT,
    `data` JSON,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_read` (`is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SESSIONS TABLE (User sessions)
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `refresh_token` VARCHAR(255),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for common queries
CREATE INDEX `idx_transactions_lookup` ON `transactions`(`store_id`, `status`, `transaction_date`);
CREATE INDEX `idx_payments_lookup` ON `payments`(`transaction_id`, `status`);
CREATE INDEX `idx_stock_lookup` ON `stock_levels`(`store_id`, `quantity_on_hand`);
CREATE INDEX `idx_products_search` ON `products`(`name`, `sku`, `barcode`);
CREATE INDEX `idx_customers_search` ON `customers`(`first_name`, `last_name`, `email`, `phone`);

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Daily sales reconciliation
CREATE PROCEDURE `sp_daily_reconciliation`(
    IN `p_store_id` BIGINT,
    IN `p_date` DATE
)
BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END), 0) as expected_cash,
        COALESCE(SUM(CASE WHEN payment_type = 'card' THEN amount ELSE 0 END), 0) as expected_card,
        COALESCE(SUM(CASE WHEN payment_type = 'gift_card' THEN amount ELSE 0 END), 0) as expected_gift_card,
        COUNT(DISTINCT t.id) as transaction_count,
        COALESCE(SUM(t.grand_total), 0) as total_sales,
        COALESCE(SUM(t.tax_total), 0) as total_tax
    FROM `transactions` t
    LEFT JOIN `payments` p ON t.id = p.transaction_id
    WHERE t.store_id = p_store_id 
        AND DATE(t.transaction_date) = p_date
        AND t.status = 'completed';
END//

-- Inventory valuation
CREATE PROCEDURE `sp_inventory_valuation`(
    IN `p_store_id` BIGINT
)
BEGIN
    SELECT 
        SUM(sl.quantity_on_hand * p.cost_price) AS total_cost_value,
        SUM(sl.quantity_on_hand * p.unit_price) AS total_retail_value,
        SUM(sl.quantity_on_hand * (p.unit_price - p.cost_price)) AS total_markup,
        COUNT(DISTINCT sl.product_id) AS unique_products,
        SUM(sl.quantity_on_hand) AS total_units,
        SUM(CASE WHEN sl.quantity_on_hand <= p.reorder_point THEN 1 ELSE 0 END) AS low_stock_count,
        SUM(CASE WHEN sl.quantity_on_hand = 0 THEN 1 ELSE 0 END) AS out_of_stock_count
    FROM `stock_levels` sl
    JOIN `products` p ON sl.product_id = p.id
    WHERE sl.store_id = p_store_id;
END//

-- Customer lifetime value calculation
CREATE PROCEDURE `sp_customer_ltv`(
    IN `p_customer_id` BIGINT
)
BEGIN
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.email,
        COUNT(t.id) as total_transactions,
        COALESCE(SUM(t.grand_total), 0) as lifetime_value,
        COALESCE(AVG(t.grand_total), 0) as average_order_value,
        MAX(t.transaction_date) as last_purchase_date,
        DATEDIFF(NOW(), MAX(t.transaction_date)) as days_since_last_purchase
    FROM `customers` c
    LEFT JOIN `transactions` t ON c.id = t.customer_id AND t.status = 'completed'
    WHERE c.id = p_customer_id
    GROUP BY c.id;
END//

DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Auto-update stock when transaction is completed
CREATE TRIGGER `trg_update_stock_on_sale`
AFTER UPDATE ON `transactions`
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE `stock_levels` sl
        JOIN `transaction_items` ti ON ti.product_id = sl.product_id
        SET sl.quantity_on_hand = sl.quantity_on_hand - ti.quantity
        WHERE ti.transaction_id = NEW.id 
            AND sl.store_id = NEW.store_id;
        
        UPDATE `customers` c
        SET loyalty_points = loyalty_points + FLOOR(NEW.grand_total / 10),
            lifetime_value = lifetime_value + NEW.grand_total,
            total_visits = total_visits + 1,
            last_visit_date = CURDATE()
        WHERE c.id = NEW.customer_id;
    END IF;
END//

-- Audit log trigger for transactions
CREATE TRIGGER `trg_audit_transaction_update`
AFTER UPDATE ON `transactions`
FOR EACH ROWBEGIN
    IF NEW.status != OLD.status OR NEW.grand_total != OLD.grand_total THEN
        INSERT INTO `audit_log` (user_id, store_id, action, entity_type, entity_id, old_values, new_values)
        VALUES (NEW.user_id, NEW.store_id, 'update', 'transaction', NEW.id, 
                JSON_OBJECT('status', OLD.status, 'grand_total', OLD.grand_total),
                JSON_OBJECT('status', NEW.status, 'grand_total', NEW.grand_total));
    END IF;
END//

-- Prevent negative stock
CREATE TRIGGER `trg_prevent_negative_stock`
BEFORE UPDATE ON `stock_levels`
FOR EACH ROW
BEGIN
    IF NEW.quantity_on_hand < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot set negative stock quantity';
    END IF;
END//

DELIMITER ;

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Daily sales summary view
CREATE OR REPLACE VIEW `vw_daily_sales` AS
SELECT 
    DATE(transaction_date) AS sale_date,
    store_id,
    COUNT(*) AS transaction_count,
    SUM(subtotal) AS total_subtotal,
    SUM(discount_total) AS total_discounts,
    SUM(tax_total) AS total_tax,
    SUM(grand_total) AS total_sales,
    AVG(grand_total) AS average_transaction_value,
    COUNT(DISTINCT customer_id) AS unique_customers
FROM `transactions`
WHERE status = 'completed'
GROUP BY DATE(transaction_date), store_id;

-- Product performance view
CREATE OR REPLACE VIEW `vw_product_performance` AS
SELECT 
    p.id AS product_id,
    p.sku,
    p.name,
    c.name AS category_name,
    COUNT(ti.id) AS times_sold,
    SUM(ti.quantity) AS total_quantity_sold,
    SUM(ti.line_total) AS total_revenue,
    AVG(ti.unit_price) AS average_selling_price,
    SUM(ti.line_total) - (SUM(ti.quantity) * p.cost_price) AS estimated_profit
FROM `products` p
LEFT JOIN `transaction_items` ti ON p.id = ti.product_id
LEFT JOIN `transactions` t ON ti.transaction_id = t.id AND t.status = 'completed'
LEFT JOIN `categories` c ON p.category_id = c.id
GROUP BY p.id;

-- Customer insights view
CREATE OR REPLACE VIEW `vw_customer_insights` AS
SELECT 
    c.id,
    c.first_name,
    c.last_name,
    c.email,
    c.phone,
    c.loyalty_tier,
    c.loyalty_points,
    COUNT(t.id) as total_transactions,
    COALESCE(SUM(t.grand_total), 0) as lifetime_value,
    COALESCE(AVG(t.grand_total), 0) as avg_order,
    MAX(t.transaction_date) as last_purchase,
    DATEDIFF(NOW(), IFNULL(MAX(t.transaction_date), c.created_at)) as days_inactive
FROM `customers` c
LEFT JOIN `transactions` t ON c.id = t.customer_id AND t.status = 'completed'
GROUP BY c.id;

-- =====================================================
-- INITIAL DATA SEEDS
-- =====================================================

-- Insert default store
INSERT IGNORE INTO `stores` (`code`, `name`, `address`, `city`, `state`, `postal_code`, `phone`, `email`, `tax_rate`) VALUES
('NLE-DTN', 'Nile Shopping Downtown', '123 Nile Avenue', 'Nile City', 'NC', '10001', '+1 (555) 123-4567', 'downtown@nileshopping.com', 10.00);

-- Insert default admin user (password: Admin@2024!)
INSERT IGNORE INTO `users` (`store_id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`) VALUES
(1, 'admin@nileshopping.com', '$2y$10$YourHashHere', 'System', 'Administrator', 'admin', 1);

-- Insert default categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `sort_order`) VALUES
('Apparel', 'apparel', 1),
('Footwear', 'footwear', 2),
('Accessories', 'accessories', 3),
('Electronics', 'electronics', 4);

-- Insert default suppliers
INSERT IGNORE INTO `suppliers` (`code`, `name`, `contact_name`, `email`, `phone`) VALUES
('SUP-NB', 'Nile Basics Co.', 'Robert White', 'orders@nilebasics.com', '+1 (555) 200-1001'),
('SUP-NS', 'Nile Sport Ltd.', 'Jennifer Green', 'purchase@nilesport.com', '+1 (555) 200-1002'),
('SUP-NA', 'Nile Accessories Inc.', 'David Brown', 'sales@nileacc.com', '+1 (555) 200-1003'),
('SUP-NT', 'Nile Technologies', 'Lisa Wong', 'orders@niletech.com', '+1 (555) 200-1004');

-- Insert default products
INSERT IGNORE INTO `products` (`sku`, `name`, `price`, `cost_price`, `stock`, `category_id`, `supplier_id`, `reorder_point`, `reorder_quantity`) VALUES
('NLE-APR-001', 'Classic Cotton T-Shirt', 29.99, 12.50, 156, 1, 1, 50, 100),
('NLE-APR-002', 'Slim Fit Denim Jeans', 79.99, 35.00, 89, 1, 1, 30, 60),
('NLE-FTW-003', 'Running Sneakers', 119.99, 55.00, 45, 2, 2, 20, 40),
('NLE-ACC-004', 'Leather Wallet', 49.99, 18.00, 234, 3, 3, 50, 100),
('NLE-ELC-005', 'Smart Watch Pro', 299.99, 150.00, 23, 4, 4, 15, 30),
('NLE-ELC-006', 'Wireless Earbuds', 89.99, 40.00, 67, 4, 4, 25, 50),
('NLE-ACC-007', 'Polarized Sunglasses', 69.99, 28.00, 112, 3, 3, 40, 80),
('NLE-ACC-008', 'Travel Backpack', 89.99, 38.00, 78, 3, 3, 30, 60);

-- Insert stock levels
INSERT IGNORE INTO `stock_levels` (`product_id`, `store_id`, `quantity_on_hand`, `reorder_point`, `reorder_quantity`)
SELECT p.id, 1, p.stock, p.reorder_point, p.reorder_quantity
FROM `products` p;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
SELECT 'Nile Shopping POS Database (nile-shopping-db) initialized successfully!' AS status;
