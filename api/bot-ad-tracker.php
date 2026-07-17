<?php
// api/bot-ad-tracker.php
require_once __DIR__ . '/../lib/db_mysqli.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function get_proxy_aware_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim(end($parts));
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return $ip;
}

$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;
if ($ad_id <= 0) {
    http_response_code(400);
    die("Invalid Ad ID.");
}

// 1. Fetch the ad to validate existence and find the destination URL and click cost
$ad = null;
$stmt_ad = $mysqli->prepare("SELECT * FROM bot_ads WHERE id = ? AND is_active = 1 LIMIT 1");
if ($stmt_ad) {
    $stmt_ad->bind_param('i', $ad_id);
    $stmt_ad->execute();
    $res_ad = $stmt_ad->get_result();
    if ($res_ad && $res_ad->num_rows > 0) {
        $ad = $res_ad->fetch_assoc();
    }
    $stmt_ad->close();
}

if (!$ad) {
    http_response_code(404);
    die("Ad campaign not found or inactive.");
}

$destination_url = !empty($ad['destination_url']) ? $ad['destination_url'] : '../index.php';

// 1b. Click-Fraud Rate Limiting sliding window check (Max 3 clicks per hour per IP)
$ip_address = get_proxy_aware_ip();
$is_fraudulent = false;

$stmt_fraud = $mysqli->prepare("
    SELECT COUNT(*) AS click_count
    FROM bot_ad_fraud_logs
    WHERE ad_id = ? AND ip_address = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
if ($stmt_fraud) {
    $stmt_fraud->bind_param('is', $ad_id, $ip_address);
    $stmt_fraud->execute();
    $res_fraud = $stmt_fraud->get_result();
    if ($row_fraud = $res_fraud->fetch_assoc()) {
        if ((int)$row_fraud['click_count'] >= 3) {
            $is_fraudulent = true;
        }
    }
    $stmt_fraud->close();
}

if ($is_fraudulent) {
    // If it exceeds this threshold, intercept the request immediately, block the budget consumption,
    // and issue a clean HTTP 302 redirect directly without charging the campaign
    header("Location: " . $destination_url, true, 302);
    exit;
}

// Log valid click in fraud logs for sliding window tracking
$stmt_log_fraud = $mysqli->prepare("INSERT INTO bot_ad_fraud_logs (ad_id, ip_address) VALUES (?, ?)");
if ($stmt_log_fraud) {
    $stmt_log_fraud->bind_param('is', $ad_id, $ip_address);
    $stmt_log_fraud->execute();
    $stmt_log_fraud->close();
}

// 2. Resolve or locate an active bot session
$session_id = null;
$user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

// Look up a bot session matching the user
if ($user_id !== null) {
    $stmt_sess = $mysqli->prepare("SELECT id FROM bot_sessions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt_sess) {
        $stmt_sess->bind_param('i', $user_id);
        $stmt_sess->execute();
        $res_sess = $stmt_sess->get_result();
        if ($res_sess && $row_sess = $res_sess->fetch_assoc()) {
            $session_id = (int)$row_sess['id'];
        }
        $stmt_sess->close();
    }
}

// 3. Prevent duplicate clicks (Abuse protection)
$is_duplicate = false;
$now_time = time();

if (!isset($_SESSION['ad_clicks_registry'])) {
    $_SESSION['ad_clicks_registry'] = [];
}

// If clicked in the last 15 seconds, treat as duplicate
if (isset($_SESSION['ad_clicks_registry'][$ad_id]) && ($now_time - $_SESSION['ad_clicks_registry'][$ad_id] < 15)) {
    $is_duplicate = true;
}

$_SESSION['ad_clicks_registry'][$ad_id] = $now_time;

// If we have a session ID, perform a database check to avoid double-charging
if (!$is_duplicate && $session_id !== null) {
    $stmt_dup = $mysqli->prepare("
        SELECT id FROM bot_ad_clicks
        WHERE ad_id = ?
          AND session_id = ?
          AND clicked_at >= DATE_SUB(NOW(), INTERVAL 15 SECOND)
        LIMIT 1
    ");
    if ($stmt_dup) {
        $stmt_dup->bind_param('ii', $ad_id, $session_id);
        $stmt_dup->execute();
        $res_dup = $stmt_dup->get_result();
        if ($res_dup && $res_dup->num_rows > 0) {
            $is_duplicate = true;
        }
        $stmt_dup->close();
    }
}

// 4. Record the click and atomically charge the sponsor budget if it's a direct sponsor & not a duplicate
if (!$is_duplicate && $ad['ad_source_type'] === 'direct_sponsor') {
    $click_cost = (float)$ad['click_cost'];

    // Only charge if the ad billing model uses budgets
    $should_charge = ($ad['ad_billing_model'] !== 'flat_rate_temporal');
    $earned_amount = $should_charge ? $click_cost : 0.00;

    $mysqli->begin_transaction();
    try {
        // Insert chronological click log
        $stmt_click = $mysqli->prepare("INSERT INTO bot_ad_clicks (ad_id, session_id, earned_amount) VALUES (?, ?, ?)");
        if ($stmt_click) {
            $stmt_click->bind_param('iid', $ad_id, $session_id, $earned_amount);
            $stmt_click->execute();
            $stmt_click->close();
        }

        // Atomically increment current spend and click count
        if ($should_charge) {
            $stmt_up = $mysqli->prepare("
                UPDATE bot_ads
                SET current_spend = current_spend + ?,
                    is_active = CASE WHEN current_spend >= max_budget THEN 0 ELSE 1 END
                WHERE id = ?
            ");
            if ($stmt_up) {
                $stmt_up->bind_param('di', $click_cost, $ad_id);
                $stmt_up->execute();
                $stmt_up->close();
            }
        }

        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        // Fallback: Proceed to redirect even if db writing failed to avoid breaking user experience
    }
}

// 5. Clean immediate HTTP 302 redirect
header("Location: " . $destination_url, true, 302);
exit;
?>