<?php
// api/dashboard-charts.php
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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to charting endpoints.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = isset($_GET['type']) ? trim($_GET['type']) : 'date'; // date, category, vendor
$vendor_uuid = null;

if ($is_provider) {
    // Resolve the logged-in provider's account details to override parameters
    $stmt_p = $mysqli->prepare("SELECT uuid FROM providers WHERE owner_user_id = ? LIMIT 1");
    if ($stmt_p) {
        $stmt_p->bind_param('i', $user['id']);
        $stmt_p->execute();
        $res_p = $stmt_p->get_result();
        if ($row_p = $res_p->fetch_assoc()) {
            $vendor_uuid = $row_p['uuid'];
        }
        $stmt_p->close();
    }
}

// Select Projection and Group By based on requested dimension
$select_projection = "";
$group_by_clause = "";
$where_clauses = ["1=1"];
$bind_types = "";
$bind_params = [];

if ($vendor_uuid) {
    $where_clauses[] = "p.uuid = ?";
    $bind_types .= "s";
    $bind_params[] = $vendor_uuid;
}

switch ($type) {
    case 'category':
        $select_projection = "cat.name AS dimension_label";
        $group_by_clause = "GROUP BY cat.id, cat.name";
        break;
    case 'vendor':
        $select_projection = "p.name AS dimension_label";
        $group_by_clause = "GROUP BY p.id, p.name";
        break;
    case 'date':
    default:
        $select_projection = "DATE_FORMAT(t.created_at, '%b %d, %Y') AS dimension_label";
        $group_by_clause = "GROUP BY DATE_FORMAT(t.created_at, '%Y-%m-%d')";
        break;
}

$sql_query = "
    SELECT
        $select_projection,
        SUM(t.gross_amount) AS total_gross,
        SUM(t.platform_fee) AS total_fee,
        SUM(t.vendor_net_amount) AS total_net
    FROM payment_transactions t
    JOIN cases c ON c.uuid = t.case_uuid
    JOIN services s ON s.id = c.service_id
    JOIN service_categories cat ON cat.id = s.category_id
    JOIN providers p ON p.id = t.provider_id
    WHERE " . implode(" AND ", $where_clauses) . "
    $group_by_clause
    ORDER BY t.created_at ASC
";

$stmt_chart = $mysqli->prepare($sql_query);
if (!$stmt_chart) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare charting statement: ' . $mysqli->error], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($bind_types !== "") {
    $stmt_chart->bind_param($bind_types, ...$bind_params);
}

$stmt_chart->execute();
$res_chart = $stmt_chart->get_result();

$labels = [];
$gross_data = [];
$fee_data = [];
$net_data = [];

if ($res_chart) {
    while ($row = $res_chart->fetch_assoc()) {
        $labels[] = $row['dimension_label'] ?: 'Unknown';
        $gross_data[] = round((float)$row['total_gross'], 2);
        $fee_data[] = round((float)$row['total_fee'], 2);
        $net_data[] = round((float)$row['total_net'], 2);
    }
}
$stmt_chart->close();

// Fallback to fill empty data so charts look clean instead of being broken/blank
if (empty($labels)) {
    $labels = ["No Data Available"];
    $gross_data = [0.00];
    $fee_data = [0.00];
    $net_data = [0.00];
}

// Structure precisely for serialization into Chart.js
echo json_encode([
    'status' => 'success',
    'type' => $type,
    'labels' => $labels,
    'datasets' => [
        [
            'label' => 'Gross Revenue (AED)',
            'data' => $gross_data,
            'borderColor' => '#1165EF',
            'backgroundColor' => 'rgba(17, 101, 239, 0.15)',
            'borderWidth' => 2,
            'fill' => true
        ],
        [
            'label' => 'Platform Fee (AED)',
            'data' => $fee_data,
            'borderColor' => '#EF4444',
            'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
            'borderWidth' => 2,
            'fill' => true
        ],
        [
            'label' => 'Vendor Net Revenue (AED)',
            'data' => $net_data,
            'borderColor' => '#10B981',
            'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
            'borderWidth' => 2,
            'fill' => true
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
