<?php
/**
 * GET /api/v1/reports/sales
 * Sales report with date filtering
 */

require_once '../../../config/pos_config.php';
require_once '../../../config/database.php';
require_once '../../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Validate authentication
$user = requireAuth();

// Query parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$groupBy = $_GET['group_by'] ?? 'day'; // day, week, month, year

$db = Database::getInstance();

// Determine group by format
switch ($groupBy) {
    case 'week':
        $dateFormat = "YEARWEEK(created_at)";
        $dateLabel = "DATE_FORMAT(created_at, '%Y Week %v')";
        break;
    case 'month':
        $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
        $dateLabel = "DATE_FORMAT(created_at, '%M %Y')";
        break;
    case 'year':
        $dateFormat = "YEAR(created_at)";
        $dateLabel = "YEAR(created_at)";
        break;
    default:
        $dateFormat = "DATE(created_at)";
        $dateLabel = "DATE(created_at)";
        $groupBy = 'day';
}

// Build query
$sql = "SELECT 
            {$dateFormat} as period,
            {$dateLabel} as period_label,
            COUNT(*) as transaction_count,
            COALESCE(SUM(subtotal), 0) as subtotal,
            COALESCE(SUM(tax), 0) as tax,
            COALESCE(SUM(total), 0) as total,
            AVG(total) as average_ticket
        FROM transactions
        WHERE DATE(created_at) BETWEEN :start_date AND :end_date
        AND status = 'completed'";

$params = [
    ':start_date' => $startDate,
    ':end_date' => $endDate
];

// Role-based filtering
if ($user['role'] !== ROLE_ADMIN && $user['role'] !== ROLE_MANAGER) {
    $sql .= " AND user_id = :user_id";
    $params[':user_id'] = $user['id'];
}

$sql .= " GROUP BY period ORDER BY period ASC";

$results = $db->fetchAll($sql, $params);

// Get totals
$totalsSql = "SELECT 
    COUNT(*) as total_transactions,
    COALESCE(SUM(total), 0) as total_sales,
    COALESCE(SUM(tax), 0) as total_tax,
    AVG(total) as avg_ticket
FROM transactions
WHERE DATE(created_at) BETWEEN :start_date AND :end_date
AND status = 'completed'";

$totals = $db->fetchOne($totalsSql, $params);

// Demo fallback
if (empty($results) && DEMO_MODE) {
    $results = [];
    $currentDate = strtotime($startDate);
    $endDateTs = strtotime($endDate);
    
    while ($currentDate <= $endDateTs) {
        $dateStr = date('Y-m-d', $currentDate);
        $results[] = [
            'period' => $dateStr,
            'period_label' => $dateStr,
            'transaction_count' => rand(20, 80),
            'subtotal' => rand(500, 2500),
            'tax' => rand(50, 250),
            'total' => rand(550, 2750),
            'average_ticket' => rand(25, 45)
        ];
        $currentDate = strtotime('+1 day', $currentDate);
    }
    
    $totals = [
        'total_transactions' => array_sum(array_column($results, 'transaction_count')),
        'total_sales' => array_sum(array_column($results, 'total')),
        'total_tax' => array_sum(array_column($results, 'tax')),
        'avg_ticket' => array_sum(array_column($results, 'average_ticket')) / count($results)
    ];
}

successResponse([
    'period' => $groupBy,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'summary' => [
        'total_transactions' => (int)($totals['total_transactions'] ?? 0),
        'total_sales' => (float)($totals['total_sales'] ?? 0),
        'total_tax' => (float)($totals['total_tax'] ?? 0),
        'average_ticket' => (float)($totals['avg_ticket'] ?? 0)
    ],
    'data' => $results
]);
