
<?php
/**
 * System Constants
 * Nile Shopping POS - Global Constants
 */

// ============================================
// USER ROLES & PERMISSIONS
// ============================================

define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_SUPERVISOR', 'supervisor');
define('ROLE_CASHIER', 'cashier');
define('ROLE_STOCK_CLERK', 'stock_clerk');
define('ROLE_VIEWER', 'viewer');

$PERMISSIONS = [
    ROLE_ADMIN => [
        'user.create', 'user.update', 'user.delete', 'user.view',
        'role.manage', 'store.configure', 'system.backup', 'system.restore',
        'transaction.view_all', 'transaction.void', 'transaction.refund',
        'product.create', 'product.update', 'product.delete',
        'inventory.adjust', 'inventory.transfer', 'report.all',
        'api.access', 'webhook.manage'
    ],
    ROLE_MANAGER => [
        'user.create', 'user.update', 'user.view',
        'transaction.view_all', 'transaction.void', 'transaction.refund',
        'product.create', 'product.update', 'inventory.adjust',
        'report.sales', 'report.inventory', 'report.employee'
    ],
    ROLE_SUPERVISOR => [
        'user.view', 'transaction.view_store', 'transaction.void',
        'product.update', 'inventory.count', 'report.daily'
    ],
    ROLE_CASHIER => [
        'transaction.create', 'transaction.view_own', 'customer.create',
        'customer.update', 'product.view', 'register.open', 'register.close'
    ],
    ROLE_STOCK_CLERK => [
        'product.view', 'inventory.count', 'inventory.adjust', 'purchase_order.create',
        'purchase_order.receive', 'supplier.view'
    ],
    ROLE_VIEWER => [
        'product.view', 'report.view_basic', 'customer.view'
    ]
];

// ============================================
// TRANSACTION STATUS
// ============================================

define('TRANSACTION_PENDING', 'pending');
define('TRANSACTION_COMPLETED', 'completed');
define('TRANSACTION_VOIDED', 'voided');
define('TRANSACTION_REFUNDED', 'refunded');
define('TRANSACTION_PARTIALLY_REFUNDED', 'partially_refunded');

// ============================================
// PAYMENT TYPES
// ============================================

define('PAYMENT_CASH', 'cash');
define('PAYMENT_CARD', 'card');
define('PAYMENT_GIFT_CARD', 'gift_card');
define('PAYMENT_STORE_CREDIT', 'store_credit');
define('PAYMENT_MOBILE', 'mobile_payment');
define('PAYMENT_BNPL', 'bnpl'); // Buy Now Pay Later

// ============================================
// INVENTORY STATUS
// ============================================

define('STOCK_IN_STOCK', 'in_stock');
define('STOCK_LOW', 'low_stock');
define('STOCK_OUT', 'out_of_stock');
define('STOCK_DISCONTINUED', 'discontinued');

// ============================================
// ORDER STATUS
// ============================================

define('ORDER_DRAFT', 'draft');
define('ORDER_CONFIRMED', 'confirmed');
define('ORDER_PROCESSING', 'processing');
define('ORDER_SHIPPED', 'shipped');
define('ORDER_DELIVERED', 'delivered');
define('ORDER_CANCELLED', 'cancelled');

// ============================================
// API RESPONSE CODES
// ============================================

define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_CONFLICT', 409);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_TOO_MANY_REQUESTS', 429);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

// ============================================
// CACHE KEYS
// ============================================

define('CACHE_PRODUCTS', 'products_');
define('CACHE_CUSTOMERS', 'customers_');
define('CACHE_SESSION', 'session_');
define('CACHE_TIER_LIMITS', 'tier_limits');

// ============================================
// QUEUE NAMES
// ============================================

define('QUEUE_TRANSACTIONS', 'transactions');
define('QUEUE_NOTIFICATIONS', 'notifications');
define('QUEUE_REPORTS', 'reports');
define('QUEUE_BACKUPS', 'backups');
