<?php
// customer/payments.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/customer_helpers.php';
require_once __DIR__ . '/../lib/csrf.php';

require_login();

// Guard access: customer role only
if (is_role('provider') || is_role('admin') || is_role('Super Admin')) {
    header('Location: ../login.php');
    exit;
}

$user = current_user();
$userId = (int)$user['id'];

// Handle payment form POST
$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_app_uuid'])) {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $appUuid = $_POST['pay_app_uuid'];
    if (pay_customer_application($userId, $appUuid)) {
        $flash_success = 'Payment processed successfully! Your balance has been updated.';
    } else {
        $flash_error = 'Failed to process payment.';
    }
}

// Fetch lists
$apps = get_customer_applications($userId);
$payments = get_customer_payments($userId);
$messages = get_customer_messages($userId);

// Unread messages count
$unread_msgs_count = 0;
foreach ($messages as $m) {
    if ($m['sender'] !== 'You') {
        $unread_msgs_count++;
    }
}

// Calculate top billing statistics
$total_paid = 0.00;
foreach ($payments as $p) {
    if ($p['status'] === 'Completed') {
        $total_paid += $p['amount'];
    }
}

$pending_paid = 0.00;
foreach ($apps as $a) {
    $due = $a['amount'] - $a['paid_amount'];
    if ($due > 0) {
        $pending_paid += $due;
    }
}

$total_invoices = count($payments);

// Generate initials
$initials = '';
$words = explode(' ', $user['name'] ?? 'Customer');
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments — GlobalWays Customer</title>
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
        <button class="btn btn-link text-white-50 p-0 d-lg-none" data-sidebar-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:rgba(255,255,255,.05)">
          <span class="avatar-circle bg-dark border border-secondary"><?= htmlspecialchars($initials) ?></span>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($user['name']) ?></div><div class="font-mono text-truncate" style="font-size:10px;color:rgba(255,255,255,.4)"><?= htmlspecialchars($user['email']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
        <a class="nav-link" href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
        <a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages <span class="badge rounded-pill"><?= $unread_msgs_count ?></span></a>
        <a class="nav-link active" href="payments.php"><i class="bi bi-credit-card"></i> Payments</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
          <div class="cp-search d-none d-sm-flex">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search applications, documents..">
          </div>
        </div>
        <div class="cp-top-actions">
          <button class="cp-bell" type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span class="cp-dot"></span></button>
          <span class="avatar-circle bg-dark"><?= htmlspecialchars($initials) ?></span>
        </div>
      </header>
      <main class="cp-page">
        <h1 class="cp-page-title"><span class="text-gradient-blue">Payments</span></h1>
        <p class="cp-page-sub">Manage your payments and transaction history</p>

        <?php if (!empty($flash_success)): ?>
          <div class="alert alert-success mt-3 mb-3"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
          <div class="alert alert-danger mt-3 mb-3"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <div class="cp-pay-stats">
          <div class="cp-pay-stat">
            <span class="cp-pay-stat-icon blue"><i class="bi bi-check-circle"></i></span>
            <div>
              <div class="cp-pay-stat-label">Total Paid</div>
              <div class="cp-pay-stat-value">AED <?= number_format($total_paid, 2) ?></div>
            </div>
          </div>
          <div class="cp-pay-stat">
            <span class="cp-pay-stat-icon orange"><i class="bi bi-clock"></i></span>
            <div>
              <div class="cp-pay-stat-label">Pending</div>
              <div class="cp-pay-stat-value warn">AED <?= number_format($pending_paid, 2) ?></div>
            </div>
          </div>
          <div class="cp-pay-stat">
            <span class="cp-pay-stat-icon grey"><i class="bi bi-receipt"></i></span>
            <div>
              <div class="cp-pay-stat-label">Total Invoices</div>
              <div class="cp-pay-stat-value"><?= $total_invoices ?></div>
            </div>
          </div>
        </div>

        <h2 class="cp-section-title mb-3">Pending Payments</h2>
        <?php
          $has_pending = false;
          foreach ($apps as $a):
            $due = $a['amount'] - $a['paid_amount'];
            if ($due <= 0) continue;
            $has_pending = true;
        ?>
          <div class="cp-pending-card mb-3">
            <div class="cp-pending-top">
              <div>
                <div class="cp-pending-title">
                  <h3><?= htmlspecialchars($a['service_name']) ?></h3>
                  <span class="cp-badge cp-badge-outline">Payment Due</span>
                </div>
                <p class="cp-pending-desc">Remaining balance for <?= htmlspecialchars($a['service_name']) ?> processing</p>
                <p class="cp-pending-id">Application ID: <?= htmlspecialchars($a['tracking_id']) ?></p>
              </div>
              <div class="cp-pending-amt">
                <strong>AED <?= number_format($due, 2) ?></strong>
                <span class="cp-pending-due"><i class="bi bi-calendar3"></i> Due: <?= date('M j, Y', strtotime($a['est_completion'])) ?></span>
              </div>
            </div>
            <div class="cp-pending-actions">
              <form method="post" action="payments.php" class="w-100 d-flex gap-2">
                <?= csrf_field(); ?>
                <input type="hidden" name="pay_app_uuid" value="<?= htmlspecialchars($a['uuid']) ?>">
                <button class="cp-btn-dark flex-grow-1 justify-content-center" type="submit">Pay Now</button>
                <a href="application-detail.php?id=<?= $a['uuid'] ?>" class="cp-btn-outline text-decoration-none d-inline-flex align-items-center justify-content-center">View Details</a>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (!$has_pending): ?>
          <div class="card border-0 shadow-sm p-4 text-center bg-white rounded-4 mb-4">
            <p class="text-muted mb-0"><i class="bi bi-check-circle-fill text-success me-1"></i> You are all caught up! No pending payments found.</p>
          </div>
        <?php endif; ?>

        <div class="cp-section-head">
          <h2 class="cp-section-title">Payment History</h2>
          <a href="#" class="cp-view-all" onclick="window.print()"><i class="bi bi-download me-1"></i>Print List</a>
        </div>

        <?php if (empty($payments)): ?>
          <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
            <p class="text-muted mb-0">No historical payments found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
            <div class="cp-hist-card">
              <span class="cp-hist-icon"><i class="bi bi-check-circle text-success"></i></span>
              <div class="cp-hist-main">
                <div class="cp-hist-title">
                  <strong><?= htmlspecialchars($p['service_name']) ?></strong>
                  <span class="cp-badge cp-badge-blue"><?= htmlspecialchars($p['status']) ?></span>
                </div>
                <div class="cp-hist-grid">
                  <div><div class="cp-meta-label">Date</div><div class="cp-meta-value"><?= date('M j, Y', strtotime($p['payment_date'])) ?></div></div>
                  <div><div class="cp-meta-label">Method</div><div class="cp-meta-value"><?= htmlspecialchars($p['method']) ?></div></div>
                  <div><div class="cp-meta-label">Invoice</div><div class="cp-meta-value"><?= htmlspecialchars($p['invoice_num']) ?></div></div>
                </div>
              </div>
              <div class="cp-hist-amt">AED <?= number_format($p['amount'], 2) ?></div>
              <button class="cp-icon-btn" type="button" aria-label="Download"><i class="bi bi-download"></i></button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <div class="cp-secure">
          <span class="cp-secure-icon"><i class="bi bi-credit-card-2-front"></i></span>
          <div>
            <h3>Secure Payments</h3>
            <p>All payments are processed through bank-grade encryption with escrow protection. Your funds are released only when milestones are complete.</p>
            <div class="cp-pay-chips">
              <span class="cp-chip">Visa</span>
              <span class="cp-chip">Mastercard</span>
              <span class="cp-chip">Apple Pay</span>
              <span class="cp-chip">Google Pay</span>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
