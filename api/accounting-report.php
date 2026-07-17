<?php
// api/accounting-report.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';

// Ensure the user is logged in
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$user = current_user();

if (!$user) {
    http_response_code(410);
    echo json_encode(['status' => 'error', 'message' => 'User session is not authenticated.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Access Gate validation: must be either Admin, Super Admin, Manager, or Provider
$is_admin_staff = is_role('Super Admin') || is_role('admin') || is_role('Manager');
$is_provider = is_role('provider');

if (!$is_admin_staff && !$is_provider) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to accounting analytics.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Sanitize and Extract Input Parameters
$start_date = isset($_GET['start_date']) && trim($_GET['start_date']) !== '' ? trim($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) && trim($_GET['end_date']) !== '' ? trim($_GET['end_date']) : null;
$vendor_uuid = isset($_GET['vendor_uuid']) && trim($_GET['vendor_uuid']) !== '' ? trim($_GET['vendor_uuid']) : null;
$category_id = isset($_GET['category_id']) && (int)$_GET['category_id'] > 0 ? (int)$_GET['category_id'] : null;
$service_id = isset($_GET['service_id']) && (int)$_GET['service_id'] > 0 ? (int)$_GET['service_id'] : null;
$group_by = isset($_GET['group_by']) ? trim($_GET['group_by']) : 'date';

// Validate date formats
if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = null;
}
if ($end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date = null;
}

// 2. Strict Role-Based Vendor Isolations
if ($is_provider) {
    // Resolve the logged-in provider's account details to override parameters
    $stmt_p = $mysqli->prepare("SELECT uuid FROM providers WHERE owner_user_id = ? LIMIT 1");
    if ($stmt_p) {
        $stmt_p->bind_param('i', $user['id']);
        $stmt_p->execute();
        $res_p = $stmt_p->get_result();
        if ($row_p = $res_p->fetch_assoc()) {
            $vendor_uuid = $row_p['uuid'];
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Active provider account profile was not found.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt_p->close();
    }
}

// 3. Assemble Dynamic Relational SQL with Bind parameters
$where_clauses = ["1=1"];
$bind_types = "";
$bind_params = [];

if ($start_date) {
    $where_clauses[] = "t.created_at >= ?";
    $bind_types .= "s";
    $bind_params[] = $start_date . ' 00:00:00';
}
if ($end_date) {
    $where_clauses[] = "t.created_at <= ?";
    $bind_types .= "s";
    $bind_params[] = $end_date . ' 23:59:59';
}
if ($vendor_uuid) {
    $where_clauses[] = "p.uuid = ?";
    $bind_types .= "s";
    $bind_params[] = $vendor_uuid;
}
if ($category_id) {
    $where_clauses[] = "s.category_id = ?";
    $bind_types .= "i";
    $bind_params[] = $category_id;
}
if ($service_id) {
    $where_clauses[] = "s.id = ?";
    $bind_types .= "i";
    $bind_params[] = $service_id;
}

// Determine Select Projection and Group By based on requested dimension
$group_by_clause = "";
$select_projection = "";

switch ($group_by) {
    case 'vendor':
        $select_projection = "p.uuid AS dimension_id, p.name AS dimension_label";
        $group_by_clause = "GROUP BY p.id, p.name";
        break;
    case 'service':
        $select_projection = "s.uuid AS dimension_id, s.title AS dimension_label";
        $group_by_clause = "GROUP BY s.id, s.title";
        break;
    case 'category':
        $select_projection = "cat.id AS dimension_id, cat.name AS dimension_label";
        $group_by_clause = "GROUP BY cat.id, cat.name";
        break;
    case 'date':
    default:
        $select_projection = "DATE_FORMAT(t.created_at, '%Y-%m-%d') AS dimension_id, DATE_FORMAT(t.created_at, '%b %d, %Y') AS dimension_label";
        $group_by_clause = "GROUP BY DATE_FORMAT(t.created_at, '%Y-%m-%d')";
        break;
}

$sql_query = "
    SELECT
        $select_projection,
        SUM(t.gross_amount) AS total_gross,
        SUM(t.platform_fee) AS total_fee,
        SUM(t.vendor_net_amount) AS total_net,
        COUNT(t.id) AS transaction_count
    FROM payment_transactions t
    JOIN cases c ON c.uuid = t.case_uuid
    JOIN services s ON s.id = c.service_id
    JOIN service_categories cat ON cat.id = s.category_id
    JOIN providers p ON p.id = t.provider_id
    WHERE " . implode(" AND ", $where_clauses) . "
    $group_by_clause
    ORDER BY dimension_id ASC
";

$stmt_report = $mysqli->prepare($sql_query);
if (!$stmt_report) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare reporting statement: ' . $mysqli->error], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($bind_types !== "") {
    $stmt_report->bind_param($bind_types, ...$bind_params);
}

$stmt_report->execute();
$res_report = $stmt_report->get_result();

$data_points = [];
$summary = [
    'gross_total' => 0.00,
    'fee_total' => 0.00,
    'net_total' => 0.00,
    'transactions_total' => 0
];

if ($res_report) {
    while ($row = $res_report->fetch_assoc()) {
        $gross = (float)$row['total_gross'];
        $fee = (float)$row['total_fee'];
        $net = (float)$row['total_net'];
        $count = (int)$row['transaction_count'];

        $data_points[] = [
            'id' => $row['dimension_id'],
            'label' => $row['dimension_label'],
            'gross' => $gross,
            'fee' => $fee,
            'net' => $net,
            'count' => $count
        ];

        $summary['gross_total'] += $gross;
        $summary['fee_total'] += $fee;
        $summary['net_total'] += $net;
        $summary['transactions_total'] += $count;
    }
}
$stmt_report->close();

echo json_encode([
    'status' => 'success',
    'dimension' => $group_by,
    'summary' => $summary,
    'data' => $data_points
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
