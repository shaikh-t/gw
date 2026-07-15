<?php
// customer/checkout.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/payment_gateway_factory.php';
require_once __DIR__ . '/../lib/notifications_helper.php';
require_once __DIR__ . '/../lib/csrf.php';

require_login();

// Guard access: customer role only
if (is_role('provider') || is_role('admin') || is_role('Super Admin')) {
    header('Location: ../login.php');
    exit;
}

$user = current_user();
$userId = (int)$user['id'];

$case_uuid = trim($_GET['case_id'] ?? '');
$case_data = null;

if ($case_uuid !== '') {
    $stmt = $mysqli->prepare("SELECT c.*, p.name as provider_name, p.owner_user_id as provider_owner_id,
                                     s.title as service_title, s.price as service_price, s.currency as service_currency
                              FROM `cases` c
                              JOIN `providers` p ON p.id = c.provider_id
                              JOIN `services` s ON s.id = c.service_id
                              WHERE c.uuid = ? AND c.customer_user_id = ? LIMIT 1");
    $stmt->bind_param('si', $case_uuid, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $case_data = $res->fetch_assoc();
    $stmt->close();
}

if (!$case_data) {
    die("<h3>Quote request not found or access denied.</h3><p><a href='applications.php'>Back to Applications</a></p>");
}

if ($case_data['status'] !== 'Quoted') {
    die("<h3>This quote cannot be booked.</h3><p>Only quote requests with status exactly 'Quoted' can be checked out. Current status: " . htmlspecialchars($case_data['status']) . "</p><p><a href='applications.php'>Back to Applications</a></p>");
}

$service_price = (float)$case_data['service_price'];
$service_currency = $case_data['service_currency'] ?: 'AED';

// Fetch active payment methods
$enabled_gateways = PaymentGatewayFactory::getEnabledGateways();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $selected_gateway_name = trim($_POST['gateway_name'] ?? '');
    $force_result = trim($_POST['force_result'] ?? 'success');

    $gateway = null;
    if ($selected_gateway_name !== '') {
        $gateway = PaymentGatewayFactory::getGateway($selected_gateway_name);
    }

    if (!$gateway || !$gateway->isEnabled()) {
        $error_message = 'Please select a valid, enabled payment method.';
    } else {
        // Initialize payment simulation
        $init_data = $gateway->initializePayment($service_price, $service_currency, $case_uuid);

        // Process simulated transaction
        $proc_data = $gateway->processPayment([
            'force_result' => $force_result,
            'client_token' => $init_data['client_secret'] ?? ($init_data['order_id'] ?? 'mock_token')
        ]);

        if ($proc_data['success'] === false) {
            $error_message = $proc_data['message'] ?: 'Payment processing failed. Please try again or choose a different card.';
        } else {
            // Payment success!
            $mysqli->begin_transaction();
            try {
                // 1. Update case status to 'Booked'
                $stmt_up = $mysqli->prepare("UPDATE `cases` SET status = 'Booked' WHERE id = ?");
                $stmt_up->bind_param('i', $case_data['id']);
                $stmt_up->execute();
                $stmt_up->close();

                // 2. Generate and Record transaction/invoice log
                $invoice_num = 'INV-2026-' . rand(1000, 9999);
                $pay_uuid = generate_uuid();
                $stmt_pay = $mysqli->prepare("INSERT INTO `customer_payments` (`uuid`, `user_id`, `service_name`, `amount`, `status`, `payment_date`, `method`, `invoice_num`) VALUES (?, ?, ?, ?, 'Completed', NOW(), ?, ?)");
                $stmt_pay->bind_param('sisdss', $pay_uuid, $userId, $case_data['service_title'], $service_price, $selected_gateway_name, $invoice_num);
                $stmt_pay->execute();
                $stmt_pay->close();

                // Also populate a tracking row in customer_applications so they can view and track progress!
                $app_uuid = generate_uuid();
                $tracking_id = 'UAE-2026-' . rand(100000, 999999);
                $stmt_app = $mysqli->prepare("INSERT INTO `customer_applications` (`uuid`, `user_id`, `service_name`, `tracking_id`, `vendor_name`, `status`, `progress`, `submitted_at`, `est_completion`, `last_update`, `next_action`, `amount`, `paid_amount`) VALUES (?, ?, ?, ?, ?, 'In Progress', 10, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 'Payment Received', 'Reviewing Requirements', ?, ?)");
                $stmt_app->bind_param('sissssdd', $app_uuid, $userId, $case_data['service_title'], $tracking_id, $case_data['provider_name'], $service_price, $service_price);
                $stmt_app->execute();
                $stmt_app->close();

                $mysqli->commit();

                // 3. Trigger booking notifications
                $customer_name = $user['name'];
                $service_title = $case_data['service_title'];

                // Target: Vendor and system Admin
                $notif_title = "New Booking Confirmed!";
                $notif_msg = "Customer $customer_name has booked the service '$service_title' with a successful payment of $service_currency " . number_format($service_price, 2) . ".";

                // Target URL paths
                $vendor_url = "vendor/quote-requests.php";
                $admin_url = "admin/dashboard.php";

                // Trigger alerts
                notify_vendor($case_data['provider_id'], $notif_title, $notif_msg, $vendor_url);
                notify_admins($notif_title, $notif_msg, $admin_url);

                $_SESSION['flash_success'] = "Payment completed successfully! Your booking for '$service_title' is confirmed.";
                header('Location: applications.php');
                exit;

            } catch (Exception $e) {
                $mysqli->rollback();
                $error_message = "Payment successful but system fulfillment failed: " . $e->getMessage();
            }
        }
    }
}

// Generate initials
$initials = '';
$words = explode(' ', $user['name'] ?? 'Customer');
foreach ($words as $w) $initials .= strtoupper(substr($w, 0, 1));
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout — GlobalWays Customer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/globalways.css" rel="stylesheet">
</head>
<body class="bg-warm">
  <div class="dashboard-wrapper d-flex">
    <aside class="dashboard-sidebar d-flex flex-column">
      <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
        <a href="../index.php" class="text-decoration-none d-flex align-items-center gap-2">
          <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:linear-gradient(135deg,#1165EF,#3F83F4)"><i class="bi bi-globe2 text-white small"></i></div>
          <div><div class="text-white font-serif small">GlobalWays</div><div class="font-mono text-uppercase" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.4)">Customer Portal</div></div>
        </a>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:rgba(255,255,255,.05)">
          <span class="avatar-circle bg-dark border border-secondary"><?= htmlspecialchars($initials) ?></span>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($user['name']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link active" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
        <a class="nav-link" href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
        <a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages</a>
        <a class="nav-link" href="payments.php"><i class="bi bi-credit-card"></i> Payments</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
      </nav>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1"><h1 class="h5 mb-0">Secure Checkout</h1></div>
        <div class="d-flex align-items-center gap-2"><span class="avatar-circle bg-dark"><?= htmlspecialchars($initials) ?></span></div>
      </header>
      <main class="cp-page">
        <div class="mb-4">
          <a href="applications.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Back to Applications</a>
        </div>

        <?php if ($error_message !== ''): ?>
          <div class="alert alert-danger mb-4"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="row g-4">
          <!-- Checkout Form -->
          <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
              <h2 class="font-serif h4 mb-3">Select Payment Method</h2>

              <?php if (empty($enabled_gateways)): ?>
                <div class="alert alert-warning py-4">
                  <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                  <strong>No Payment Gateways Available:</strong> The administrator has not configured or enabled any active payment options (Stripe, PayPal, or Authorize.net) in the Admin settings panel. Please contact support.
                </div>
              <?php else: ?>
                <form action="checkout.php?case_id=<?= htmlspecialchars($case_uuid) ?>" method="post">
                  <?= csrf_field(); ?>

                  <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($enabled_gateways as $idx => $gw): ?>
                      <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between cursor-pointer gw-option-card">
                        <div class="d-flex align-items-center gap-3">
                          <input type="radio" name="gateway_name" value="<?= htmlspecialchars($gw->getName()) ?>" <?= $idx === 0 ? 'checked' : '' ?> class="form-check-input">
                          <div>
                            <strong class="text-dark d-block"><?= htmlspecialchars($gw->getName()) ?> Gateway</strong>
                            <small class="text-muted"><?= $gw->isSandbox() ? 'Sandbox Mode Active (Simulated)' : 'Live Transaction Connection' ?></small>
                          </div>
                        </div>
                        <i class="bi bi-shield-check text-primary fs-4"></i>
                      </label>
                    <?php endforeach; ?>
                  </div>

                  <!-- Sandbox testing toggle block -->
                  <div class="p-3 bg-light rounded-3 mb-4 border border-warning border-opacity-50">
                    <h5 class="h6 text-warning-emphasis fw-bold mb-2"><i class="bi bi-bug-fill me-1"></i> Sandbox / Testing Operations</h5>
                    <p class="small text-secondary mb-3">As this is a development environment, you can force the payment processing logic to simulate a successful or failed card transaction outcome.</p>
                    <div class="btn-group w-100" role="group" aria-label="Testing options">
                      <input type="radio" class="btn-check" name="force_result" id="force_success" value="success" checked>
                      <label class="btn btn-outline-success" for="force_success"><i class="bi bi-check-circle me-1"></i> Force SUCCESS</label>

                      <input type="radio" class="btn-check" name="force_result" id="force_fail" value="fail">
                      <label class="btn btn-outline-danger" for="force_fail"><i class="bi bi-x-circle me-1"></i> Force FAILURE</label>
                    </div>
                  </div>

                  <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-lock-fill me-1"></i> Confirm & Authorize Payment
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <!-- Order Summary Sidebar -->
          <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
              <h3 class="font-serif h5 mb-3">Booking Summary</h3>
              <div class="pb-3 border-bottom mb-3">
                <div class="small text-muted mb-1">Service Provider</div>
                <strong class="text-dark"><?= htmlspecialchars($case_data['provider_name']) ?></strong>
              </div>
              <div class="pb-3 border-bottom mb-3">
                <div class="small text-muted mb-1">Requested Service Option</div>
                <strong class="text-dark"><?= htmlspecialchars($case_data['service_title']) ?></strong>
              </div>
              <div class="d-flex justify-content-between align-items-center py-2 mb-3">
                <span class="fw-bold text-dark">Price Quote</span>
                <span class="font-mono fs-4 fw-bold text-primary">
                  <?= htmlspecialchars($service_currency) ?> <?= number_format($service_price, 2) ?>
                </span>
              </div>
              <div class="p-3 bg-warm rounded-3 small text-secondary">
                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                Your payment is secure. Funds will be deposited in protected escrow and only transferred to the vendor upon successful approval of your documentation.
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
