<?php
/**
 * Nile POS API Entry Point
 * Routes all API requests
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set timezone
date_default_timezone_set('America/New_York');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$requestUri = strtok($requestUri, '?');

// Remove /api/v1 prefix
$path = preg_replace('/^\/api\/v1/', '', $requestUri);
$path = trim($path, '/');

// Route mapping
$routes = [
    // Auth routes
    'auth/login' => ['file' => 'auth/login.php', 'method' => 'POST'],
    'auth/logout' => ['file' => 'auth/logout.php', 'method' => 'POST'],
    'auth/verify' => ['file' => 'auth/verify.php', 'method' => 'GET'],
    'auth/refresh' => ['file' => 'auth/refresh.php', 'method' => 'POST'],
    
    // Product routes
    'products/list' => ['file' => 'products/list.php', 'method' => 'GET'],
    'products/search' => ['file' => 'products/search.php', 'method' => 'GET'],
    'products/barcode' => ['file' => 'products/barcode.php', 'method' => 'GET'],
    'products/get' => ['file' => 'products/get.php', 'method' => 'GET'],
    
    // Transaction routes
    'transactions/create' => ['file' => 'transactions/create.php', 'method' => 'POST'],
    'transactions/get' => ['file' => 'transactions/get.php', 'method' => 'GET'],
    'transactions/refund' => ['file' => 'transactions/refund.php', 'method' => 'POST'],
    'transactions/void' => ['file' => 'transactions/void.php', 'method' => 'POST'],
    
    // Customer routes
    'customers/list' => ['file' => 'customers/list.php', 'method' => 'GET'],
    'customers/create' => ['file' => 'customers/create.php', 'method' => 'POST'],
    'customers/update' => ['file' => 'customers/update.php', 'method' => 'PUT'],
    'customers/lookup' => ['file' => 'customers/lookup.php', 'method' => 'GET'],
    
    // Inventory routes
    'inventory/check' => ['file' => 'inventory/check.php', 'method' => 'GET'],
    'inventory/adjust' => ['file' => 'inventory/adjust.php', 'method' => 'POST'],
    
    // Report routes
    'reports/sales' => ['file' => 'reports/sales.php', 'method' => 'GET'],
    'reports/dashboard' => ['file' => 'reports/dashboard.php', 'method' => 'GET'],
    
    // Health check
    'health' => ['file' => 'health.php', 'method' => 'GET'],
    '' => ['file' => 'health.php', 'method' => 'GET']
];

// Find matching route
$route = null;
foreach ($routes as $pattern => $routeConfig) {
    if ($path === $pattern) {
        $route = $routeConfig;
        break;
    }
}

if (!$route) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    exit;
}

// Check method
if ($route['method'] !== $requestMethod) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include the API file
$apiFile = __DIR__ . '/../api/v1/' . $route['file'];

if (!file_exists($apiFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'API handler not found']);
    exit;
}

require_once $apiFile;
