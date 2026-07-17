<?php
// api/ad-revenue-charts.php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';

// Strict Super Admin Access Lock
if (!is_role('Super Admin')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Super Admin role required.']);
    exit;
}

$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Default to last 30 days if not set
if (empty($start_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
}
if (empty($end_date)) {
    $end_date = date('Y-m-d');
}

// 1. Daily Revenue Breakdown (Date-wise sum of earned_amount)
$daily_labels = [];
$daily_values = [];

$query_daily = "
    SELECT DATE(clicked_at) as click_date, SUM(earned_amount) as total_earned
    FROM bot_ad_clicks
    WHERE clicked_at >= ? AND clicked_at <= ?
    GROUP BY DATE(clicked_at)
    ORDER BY click_date ASC
";

$stmt_daily = $mysqli->prepare($query_daily);
if ($stmt_daily) {
    // Append time context for absolute date boundaries
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    $stmt_daily->bind_param('ss', $start_datetime, $end_datetime);
    $stmt_daily->execute();
    $res_daily = $stmt_daily->get_result();

    // Fill in a date registry map to make sure every single date in the range exists (zero-filled if no clicks)
    $curr = strtotime($start_date);
    $last = strtotime($end_date);
    $date_map = [];
    while ($curr <= $last) {
        $date_map[date('Y-m-d', $curr)] = 0.00;
        $curr = strtotime('+1 day', $curr);
    }

    while ($row = $res_daily->fetch_assoc()) {
        $date_map[$row['click_date']] = (float)$row['total_earned'];
    }

    $daily_labels = array_keys($date_map);
    $daily_values = array_values($date_map);

    $stmt_daily->close();
}

// 2. Campaign Spend / Click Breakdown (Campaign name and earned amount)
$campaign_labels = [];
$campaign_values = [];

$query_campaign = "
    SELECT ba.campaign_name, SUM(bac.earned_amount) as campaign_revenue
    FROM bot_ad_clicks bac
    JOIN bot_ads ba ON bac.ad_id = ba.id
    WHERE bac.clicked_at >= ? AND bac.clicked_at <= ?
    GROUP BY ba.id
    ORDER BY campaign_revenue DESC
";

$stmt_campaign = $mysqli->prepare($query_campaign);
if ($stmt_campaign) {
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    $stmt_campaign->bind_param('ss', $start_datetime, $end_datetime);
    $stmt_campaign->execute();
    $res_campaign = $stmt_campaign->get_result();

    while ($row = $res_campaign->fetch_assoc()) {
        $campaign_labels[] = $row['campaign_name'];
        $campaign_values[] = (float)$row['campaign_revenue'];
    }
    $stmt_campaign->close();
}

echo json_encode([
    'status' => 'success',
    'date_range' => [
        'start' => $start_date,
        'end' => $end_date
    ],
    'daily_breakdown' => [
        'labels' => $daily_labels,
        'data' => $daily_values
    ],
    'campaign_breakdown' => [
        'labels' => $campaign_labels,
        'data' => $campaign_values
    ]
], JSON_UNESCAPED_UNICODE);
exit;
?>