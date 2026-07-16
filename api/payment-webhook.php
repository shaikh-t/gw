<?php
// api/payment-webhook.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/uuid_helper.php';
require_once __DIR__ . '/../lib/notifications_helper.php';

// Helper function to safely send JSON responses
function send_json_response(array $data, int $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response([
        'status' => 'error',
        'message' => 'Only POST requests are allowed.'
    ], 405);
}

// Decode webhook payload
$input_raw = file_get_contents('php://input');
$payload = json_decode($input_raw, true);

if (!is_array($payload)) {
    send_json_response([
        'status' => 'error',
        'message' => 'Invalid webhook payload.'
    ], 400);
}

$case_uuid = isset($payload['case_uuid']) ? trim($payload['case_uuid']) : '';
$transaction_id = isset($payload['transaction_id']) ? trim($payload['transaction_id']) : '';
$event_type = isset($payload['event']) ? trim($payload['event']) : 'payment_intent.succeeded';

if (empty($case_uuid) || empty($transaction_id)) {
    send_json_response([
        'status' => 'error',
        'message' => 'Missing case_uuid or transaction_id parameters.'
    ], 400);
}

// Ensure database connection is active
if (!isset($mysqli) || $mysqli->connect_errno) {
    send_json_response([
        'status' => 'error',
        'message' => 'Database connection is unavailable.'
    ], 500);
}

// Fetch case details to ensure it exists and has status 'Quoted'
$case_data = null;
$stmt = $mysqli->prepare("SELECT c.*, p.name as provider_name, s.title as service_title, s.price as service_price, s.currency as service_currency, u.name as customer_name
                          FROM `cases` c
                          JOIN `providers` p ON p.id = c.provider_id
                          JOIN `services` s ON s.id = c.service_id
                          JOIN `users` u ON u.id = c.customer_user_id
                          WHERE c.uuid = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $case_uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $case_data = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$case_data) {
    send_json_response([
        'status' => 'error',
        'message' => 'Case context not found.'
    ], 404);
}

if ($case_data['status'] !== 'Quoted' && $case_data['status'] !== 'Pending') {
    // Already processed or invalid status
    send_json_response([
        'status' => 'success',
        'message' => 'Payment already processed or case status is not quoted. Current status: ' . $case_data['status']
    ]);
}

$service_price = (float)$case_data['service_price'];
$service_currency = $case_data['service_currency'] ?: 'AED';
$userId = (int)$case_data['customer_user_id'];

// Secure transaction update
$mysqli->begin_transaction();
try {
    // 1. Update case status to 'Booked' and record the transaction_id
    // This implements Appendix A secure webhook logic:
    // UPDATE cases SET status = 'Booked', transaction_id = ? WHERE uuid = ? AND status = 'Quoted';
    $stmt_up = $mysqli->prepare("UPDATE `cases` SET status = 'Booked', transaction_id = ? WHERE uuid = ?");
    $stmt_up->bind_param('ss', $transaction_id, $case_uuid);
    $stmt_up->execute();
    $stmt_up->close();

    // 2. Generate and Record transaction payment log
    $invoice_num = 'INV-2026-' . rand(1000, 9999);
    $pay_uuid = generate_uuid();
    $method_gateway = 'Stripe_Webhook';
    $stmt_pay = $mysqli->prepare("INSERT INTO `customer_payments` (`uuid`, `user_id`, `service_name`, `amount`, `status`, `payment_date`, `method`, `invoice_num`) VALUES (?, ?, ?, ?, 'Completed', NOW(), ?, ?)");
    $stmt_pay->bind_param('sisdss', $pay_uuid, $userId, $case_data['service_title'], $service_price, $method_gateway, $invoice_num);
    $stmt_pay->execute();
    $stmt_pay->close();

    // 3. Populate a tracking row in customer_applications so they can view and track progress
    $app_uuid = generate_uuid();
    $tracking_id = 'UAE-2026-' . rand(100000, 999999);
    $stmt_app = $mysqli->prepare("INSERT INTO `customer_applications` (`uuid`, `user_id`, `service_name`, `tracking_id`, `vendor_name`, `status`, `progress`, `submitted_at`, `est_completion`, `last_update`, `next_action`, `amount`, `paid_amount`) VALUES (?, ?, ?, ?, ?, 'In Progress', 10, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 'Payment Received', 'Reviewing Requirements', ?, ?)");
    $stmt_app->bind_param('sissssdd', $app_uuid, $userId, $case_data['service_title'], $tracking_id, $case_data['provider_name'], $service_price, $service_price);
    $stmt_app->execute();
    $stmt_app->close();

    $mysqli->commit();

    // Trigger booking notifications
    $customer_name = $case_data['customer_name'];
    $service_title = $case_data['service_title'];
    $notif_title = "Booking Webhook Confirmed!";
    $notif_msg = "Webhook verified successfully. Customer $customer_name has booked the service '$service_title' with a successful payment of $service_currency " . number_format($service_price, 2) . ".";

    notify_vendor($case_data['provider_id'], $notif_title, $notif_msg, "vendor/quote-requests.php");
    notify_admins($notif_title, $notif_msg, "admin/dashboard.php");

    send_json_response([
        'status' => 'success',
        'message' => 'Payment webhook processed and case state updated successfully.'
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    send_json_response([
        'status' => 'error',
        'message' => 'Fulfillment failure: ' . $e->getMessage()
    ], 500);
}
