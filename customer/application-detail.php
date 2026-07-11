<?php
// customer/application-detail.php
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

$appUuid = $_GET['id'] ?? '';
$app = get_customer_application($userId, $appUuid);

if (!$app) {
    // If no app selected or not found, grab the first one as fallback
    $apps = get_customer_applications($userId);
    if (!empty($apps)) {
        $app = $apps[0];
        $appUuid = $app['uuid'];
    } else {
        die("Application not found.");
    }
}

// Handle Form POST Actions
$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'pay') {
        if (pay_customer_application($userId, $appUuid)) {
            $flash_success = 'Payment processed successfully! Your balance has been updated.';
            // Refresh app data
            $app = get_customer_application($userId, $appUuid);
        } else {
            $flash_error = 'Failed to process payment. Please try again.';
        }
    } else if ($action === 'upload') {
        $docUuid = $_POST['doc_uuid'] ?? '';
        $fileName = $_FILES['doc_file']['name'] ?? '';
        if ($docUuid && $fileName) {
            if (upload_customer_document($userId, $docUuid, $fileName)) {
                $flash_success = 'Document uploaded and verified successfully!';
            } else {
                $flash_error = 'Failed to upload document.';
            }
        } else {
            $flash_error = 'Please select a valid file to upload.';
        }
    }
}

// Fetch lists after potential database changes
$docs = get_customer_documents($userId);
$messages = get_customer_messages($userId);

// Unread messages count
$unread_msgs_count = 0;
foreach ($messages as $m) {
    if ($m['sender'] !== 'You') {
        $unread_msgs_count++;
    }
}

// Generate initials
$initials = '';
$words = explode(' ', $user['name'] ?? 'Customer');
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';

// Determine outstanding/remaining balance
$remaining_balance = $app['amount'] - $app['paid_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($app['service_name']) ?> — GlobalWays Customer</title>
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
        <a class="nav-link active" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
        <a class="nav-link" href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
        <a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages <span class="badge rounded-pill"><?= $unread_msgs_count ?></span></a>
        <a class="nav-link" href="payments.php"><i class="bi bi-credit-card"></i> Payments</a>
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
        <a href="applications.php" class="cp-back-link">← Back to Applications</a>

        <?php if (!empty($flash_success)): ?>
          <div class="alert alert-success mt-3 mb-3"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
          <div class="alert alert-danger mt-3 mb-3"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <div class="cp-detail-head">
          <div>
            <h1 class="cp-page-title"><span class="text-gradient-blue"><?= htmlspecialchars($app['service_name']) ?></span></h1>
            <p class="cp-page-sub">Tracking ID: <?= htmlspecialchars($app['tracking_id']) ?></p>
          </div>
          <span class="cp-badge cp-badge-dark"><?= htmlspecialchars($app['status']) ?></span>
        </div>

        <div class="row g-4">
          <div class="col-lg-8">
            <!-- Progress -->
            <div class="cp-card cp-progress-card">
              <div class="cp-section-head mb-3">
                <h2 class="cp-section-title"><i class="bi bi-stars me-2" style="font-size:1rem;color:#1A73E8"></i>Application Progress</h2>
              </div>
              <div class="cp-progress-meta"><span>Overall Progress</span><span><?= (int)$app['progress'] ?>%</span></div>
              <div class="cp-progress mb-4"><span style="width:<?= (int)$app['progress'] ?>%"></span></div>

              <div class="cp-stepper">
                <div class="cp-step done">
                  <div class="cp-step-marker"><i class="bi bi-check-lg"></i></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Application Received</div>
                    <div class="cp-step-time"><?= date('M j, Y', strtotime($app['submitted_at'])) ?> · 10:30 AM</div>
                    <ul class="cp-step-list">
                      <li>Application submitted successfully</li>
                      <li>Initial payment processed</li>
                      <li>Confirmation email sent</li>
                    </ul>
                  </div>
                </div>
                <div class="cp-step <?= $app['progress'] >= 45 ? 'done' : '' ?>">
                  <div class="cp-step-marker"><?= $app['progress'] >= 45 ? '<i class="bi bi-check-lg"></i>' : '' ?></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Document Verification</div>
                    <div class="cp-step-time"><?= date('M j, Y', strtotime($app['submitted_at'] . ' + 1 day')) ?> · 2:15 PM</div>
                    <ul class="cp-step-list">
                      <li>Passport verified</li>
                      <li>Bank statements approved</li>
                      <li>Photographs validated</li>
                    </ul>
                  </div>
                </div>
                <div class="cp-step <?= $app['progress'] >= 65 ? 'done' : '' ?>">
                  <div class="cp-step-marker"><?= $app['progress'] >= 65 ? '<i class="bi bi-check-lg"></i>' : '' ?></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Government Submission</div>
                    <div class="cp-step-time"><?= date('M j, Y', strtotime($app['submitted_at'] . ' + 2 days')) ?> · 11:00 AM</div>
                    <ul class="cp-step-list">
                      <li>Submitted to UAE authorities</li>
                      <li>Application reference generated</li>
                    </ul>
                  </div>
                </div>
                <div class="cp-step <?= $app['progress'] == 65 ? 'current' : ($app['progress'] > 65 ? 'done' : '') ?>">
                  <div class="cp-step-marker"><?= $app['progress'] == 65 ? '<span class="cp-step-spinner"></span>' : ($app['progress'] > 65 ? '<i class="bi bi-check-lg"></i>' : '') ?></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Medical Test</div>
                    <div class="cp-step-time"><?= date('M j, Y', strtotime($app['submitted_at'] . ' + 7 days')) ?> · 9:00 AM</div>
                    <ul class="cp-step-list">
                      <li>Appointment scheduled at Dubai Healthcare City</li>
                      <li>Bring passport, receipt, and medical form</li>
                    </ul>
                  </div>
                </div>
                <div class="cp-step <?= $app['progress'] == 90 ? 'current' : ($app['progress'] > 90 ? 'done' : '') ?>">
                  <div class="cp-step-marker"><?= $app['progress'] == 90 ? '<span class="cp-step-spinner"></span>' : ($app['progress'] > 90 ? '<i class="bi bi-check-lg"></i>' : '') ?></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Emirates ID</div>
                    <div class="cp-step-time"><?= $app['progress'] >= 90 ? 'In Progress' : 'Pending' ?></div>
                  </div>
                </div>
                <div class="cp-step <?= $app['progress'] == 100 ? 'done' : '' ?>">
                  <div class="cp-step-marker"><?= $app['progress'] == 100 ? '<i class="bi bi-check-lg"></i>' : '' ?></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Approved</div>
                    <div class="cp-step-time">Pending</div>
                  </div>
                </div>
                <div class="cp-step <?= $app['progress'] == 100 ? 'done' : '' ?>">
                  <div class="cp-step-marker"><?= $app['progress'] == 100 ? '<i class="bi bi-check-lg"></i>' : '' ?></div>
                  <div class="cp-step-body">
                    <div class="cp-step-title">Completed</div>
                    <div class="cp-step-time">Est. <?= date('M j, Y', strtotime($app['est_completion'])) ?></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Documents -->
            <div class="cp-card">
              <h3 class="cp-card-title">Documents</h3>
              <div class="cp-detail-docs">
                <?php foreach ($docs as $d): ?>
                  <div class="cp-detail-doc">
                    <div class="cp-detail-doc-left">
                      <?php if ($d['status'] === 'Verified'): ?>
                        <i class="bi bi-check-circle-fill ok"></i>
                      <?php elseif ($d['status'] === 'Expiring Soon'): ?>
                        <i class="bi bi-exclamation-circle-fill warn"></i>
                      <?php else: ?>
                        <i class="bi bi-exclamation-circle-fill text-secondary"></i>
                      <?php endif; ?>
                      <div>
                        <strong><?= htmlspecialchars($d['name']) ?></strong>
                        <span>
                          <?php if ($d['status'] === 'Verified'): ?>
                            Verified · <?= date('M j, Y', strtotime($d['uploaded_at'])) ?>
                          <?php elseif ($d['status'] === 'Expiring Soon'): ?>
                            Expiring Soon · <?= htmlspecialchars($d['expires_at']) ?>
                          <?php else: ?>
                            Not uploaded yet
                          <?php endif; ?>
                        </span>
                      </div>
                    </div>
                    <?php if ($d['status'] === 'Verified' || $d['status'] === 'Expiring Soon'): ?>
                      <button class="cp-icon-btn" type="button" aria-label="Download"><i class="bi bi-download"></i></button>
                    <?php else: ?>
                      <form method="post" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="upload">
                        <input type="hidden" name="doc_uuid" value="<?= $d['uuid'] ?>">
                        <input type="file" name="doc_file" class="form-control form-control-sm border-0 bg-light" style="width:160px; font-size:0.75rem;" required>
                        <button class="cp-btn-dark" type="submit" style="padding:0.4rem 0.9rem;font-size:0.78rem"><i class="bi bi-upload"></i> Upload</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Activity Log -->
            <div class="cp-card">
              <h3 class="cp-card-title">Activity Log</h3>
              <div class="cp-activity-log">
                <div class="cp-log-item">
                  <div class="cp-log-dot"></div>
                  <div>
                    <strong>Status updated to <?= htmlspecialchars($app['next_action']) ?></strong>
                    <span><?= htmlspecialchars($app['last_update']) ?> · by <?= htmlspecialchars($app['vendor_name']) ?></span>
                  </div>
                </div>
                <div class="cp-log-item">
                  <div class="cp-log-dot"></div>
                  <div>
                    <strong>Application submitted to government</strong>
                    <span>Jun 3, 2026 · by <?= htmlspecialchars($app['vendor_name']) ?></span>
                  </div>
                </div>
                <div class="cp-log-item">
                  <div class="cp-log-dot"></div>
                  <div>
                    <strong>Documents verified</strong>
                    <span>Jun 2, 2026 · by <?= htmlspecialchars($app['vendor_name']) ?></span>
                  </div>
                </div>
                <div class="cp-log-item">
                  <div class="cp-log-dot"></div>
                  <div>
                    <strong>Application created</strong>
                    <span><?= date('M j, Y', strtotime($app['submitted_at'])) ?> · by You</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <!-- Application Details -->
            <div class="cp-detail-aside">
              <h3 class="cp-card-title">Application Details</h3>
              <div class="cp-aside-row">
                <span class="cp-aside-label">Service</span>
                <strong><?= htmlspecialchars($app['service_name']) ?></strong>
              </div>
              <div class="cp-aside-row">
                <span class="cp-aside-label">Tracking ID</span>
                <div class="cp-tracking-box"><?= htmlspecialchars($app['tracking_id']) ?></div>
              </div>
              <div class="cp-aside-row">
                <span class="cp-aside-label">Submitted</span>
                <strong><?= date('M j, Y', strtotime($app['submitted_at'])) ?></strong>
              </div>
              <div class="cp-aside-row">
                <span class="cp-aside-label">Est. Completion</span>
                <strong><?= date('M j, Y', strtotime($app['est_completion'])) ?></strong>
              </div>

              <div class="cp-aside-divider"></div>
              <h4 class="cp-aside-subhead">Payment Summary</h4>
              <div class="cp-pay-line"><span>Total Amount</span><strong>AED <?= number_format($app['amount'], 2) ?></strong></div>
              <div class="cp-pay-line"><span>Paid</span><strong class="paid">AED <?= number_format($app['paid_amount'], 2) ?></strong></div>
              <div class="cp-pay-line"><span>Remaining</span><strong>AED <?= number_format($remaining_balance, 2) ?></strong></div>

              <?php if ($remaining_balance > 0): ?>
                <form method="post" class="mt-3">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="pay">
                  <button type="submit" class="cp-btn-dark w-100 justify-content-center">Make Payment</button>
                </form>
              <?php else: ?>
                <button class="cp-btn-outline w-100 justify-content-center mt-3" disabled><i class="bi bi-check2-all text-success me-1"></i> Fully Paid</button>
              <?php endif; ?>
            </div>

            <!-- Vendor -->
            <div class="cp-card">
              <h3 class="cp-card-title">Vendor Information</h3>
              <div class="cp-vendor-name"><?= htmlspecialchars($app['vendor_name']) ?></div>
              <div class="cp-vendor-line"><i class="bi bi-telephone"></i> +971 4 123 4567</div>
              <div class="cp-vendor-line"><i class="bi bi-envelope"></i> support@globalways.ae</div>
              <a href="messages.php" class="cp-btn-outline w-100 justify-content-center mt-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-chat-dots"></i> Contact Vendor
              </a>
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
