<?php
// api/entry-point-charts.php
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

// Access Gate validation: must be administrative
$is_admin_staff = is_role('Super Admin') || is_role('admin') || is_role('Manager');
if (!$is_admin_staff) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to analytical endpoints.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Query active session totals grouped by entry_point
$sql = "
    SELECT COALESCE(NULLIF(TRIM(entry_point), ''), 'general_widget') AS ep, COUNT(id) AS session_count
    FROM bot_sessions
    GROUP BY ep
    ORDER BY session_count DESC
";

$res = $mysqli->query($sql);

$labels = [];
$session_data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $labels[] = ucwords(str_replace('_', ' ', $row['ep']));
        $session_data[] = (int)$row['session_count'];
    }
    $res->free();
}

if (empty($labels)) {
    $labels = ["General Widget"];
    $session_data = [0];
}

echo json_encode([
    'status' => 'success',
    'labels' => $labels,
    'datasets' => [
        [
            'label' => 'Active Bot Sessions',
            'data' => $session_data,
            'backgroundColor' => [
                'rgba(17, 101, 239, 0.7)',
                'rgba(16, 185, 129, 0.7)',
                'rgba(245, 158, 11, 0.7)',
                'rgba(239, 68, 68, 0.7)',
                'rgba(139, 92, 246, 0.7)'
            ],
            'borderColor' => [
                '#1165EF',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6'
            ],
            'borderWidth' => 1
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
