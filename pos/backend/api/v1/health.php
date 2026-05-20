<?php
/**
 * GET /api/v1/health
 * Health check endpoint for monitoring
 */

header('Content-Type: application/json');

$status = 'healthy';
$checks = [];

// Check database
try {
    $db = Database::getInstance();
    $db->fetchOne("SELECT 1");
    $checks['database'] = 'ok';
} catch (Exception $e) {
    $status = 'degraded';
    $checks['database'] = 'error: ' . $e->getMessage();
}

// Check Redis (if configured)
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST ?? 'localhost', REDIS_PORT ?? 6379);
        $checks['redis'] = 'ok';
    } catch (Exception $e) {
        $checks['redis'] = 'error: ' . $e->getMessage();
    }
} else {
    $checks['redis'] = 'not_installed';
}

// Get system info
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
    'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
];

successResponse([
    'status' => $status,
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks,
    'system' => $systemInfo
]);
