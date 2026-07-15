<?php
// customer/applications.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/customer_helpers.php';

require_login();

// Guard access: customer role only
if (is_role('provider') || is_role('admin') || is_role('Super Admin')) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../lib/db_mysqli.php';

$user = current_user();
$userId = (int)$user['id'];

$apps = get_customer_applications($userId);
$messages = get_customer_messages($userId);

// Fetch customer cases / quote requests from db
$customer_cases_sql = "SELECT c.*, p.name as provider_name, s.title as service_title, s.price as service_price, s.currency as service_currency
                       FROM `cases` c
                       JOIN `providers` p ON p.id = c.provider_id
                       JOIN `services` s ON s.id = c.service_id
                       WHERE c.customer_user_id = ?
                       ORDER BY c.created_at DESC";
$stmt_cases = $mysqli->prepare($customer_cases_sql);
$stmt_cases->bind_param('i', $userId);
$stmt_cases->execute();
$res_cases = $stmt_cases->get_result();
$customer_cases = [];
while ($row = $res_cases->fetch_assoc()) {
    $customer_cases[] = $row;
}
$stmt_cases->close();

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

// Get current filter
$filter = $_GET['filter'] ?? 'all';

$filtered_apps = [];
foreach ($apps as $a) {
    if ($filter === 'completed') {
        if ($a['status'] === 'Completed') $filtered_apps[] = $a;
    } else if ($filter === 'progress') {
        if ($a['status'] !== 'Completed' && $a['status'] !== 'Pending') $filtered_apps[] = $a;
    } else if ($filter === 'pending') {
        if ($a['status'] === 'Pending') $filtered_apps[] = $a;
    } else {
        $filtered_apps[] = $a;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applications — GlobalWays Customer</title>
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
        <h1 class="cp-page-title">My <span class="text-gradient-blue">Applications</span></h1>
        <p class="cp-page-sub">Track and manage all your visa and documentation applications</p>

        <div class="cp-toolbar">
          <div class="cp-toolbar-search">
            <i class="bi bi-search text-muted"></i>
            <input type="search" id="appSearch" placeholder="Search by service or vendor..." onkeyup="filterSearch()">
          </div>
          <div class="cp-filters">
            <a href="applications.php?filter=all" class="cp-filter <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="applications.php?filter=progress" class="cp-filter <?= $filter === 'progress' ? 'active' : '' ?>">In Progress</a>
            <a href="applications.php?filter=completed" class="cp-filter <?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
            <a href="applications.php?filter=pending" class="cp-filter <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
          </div>
        </div>

        <div id="appsList" class="mb-5">
          <?php if (empty($filtered_apps)): ?>
            <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
              <p class="text-muted mb-0">No applications found matching your criteria.</p>
            </div>
          <?php else: ?>
            <?php foreach ($filtered_apps as $a): ?>
              <a href="application-detail.php?id=<?= $a['uuid'] ?>" class="cp-app-row text-decoration-none d-block app-item">
                <div class="cp-app-row-top">
                  <div>
                    <div class="cp-app-row-title">
                      <h3 class="app-service-name"><?= htmlspecialchars($a['service_name']) ?></h3>
                      <span class="cp-badge <?= $a['status'] === 'Completed' ? 'cp-badge-blue' : ($a['status'] === 'Document Review' ? 'cp-badge-outline' : 'cp-badge-dark') ?>">
                        <?= htmlspecialchars($a['status']) ?>
                      </span>
                    </div>
                    <div class="cp-app-meta">
                      <span>Tracking: <?= htmlspecialchars($a['tracking_id']) ?></span>
                      <span class="app-vendor-name">Vendor: <?= htmlspecialchars($a['vendor_name']) ?></span>
                    </div>
                  </div>
                  <div class="cp-app-amount">
                    <strong>AED <?= number_format($a['amount'], 2) ?></strong>
                    <span>Total Amount</span>
                  </div>
                </div>
                <div class="cp-progress-meta"><span>Progress</span><span><?= (int)$a['progress'] ?>%</span></div>
                <div class="cp-progress"><span style="width:<?= (int)$a['progress'] ?>%"></span></div>
                <div class="cp-meta-grid">
                  <div>
                    <div class="cp-meta-label">Submitted</div>
                    <div class="cp-meta-value"><i class="bi bi-calendar3"></i> <?= date('M j, Y', strtotime($a['submitted_at'])) ?></div>
                  </div>
                  <div>
                    <div class="cp-meta-label">Est. Completion</div>
                    <div class="cp-meta-value"><i class="bi bi-clock"></i> <?= date('M j, Y', strtotime($a['est_completion'])) ?></div>
                  </div>
                  <div>
                    <div class="cp-meta-label">Last Update</div>
                    <div class="cp-meta-value"><?= htmlspecialchars($a['last_update']) ?></div>
                  </div>
                  <div>
                    <div class="cp-meta-label">Next Action</div>
                    <div class="cp-meta-value <?= $a['next_action'] === 'None' ? 'ok' : '' ?>"><?= htmlspecialchars($a['next_action']) ?></div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- My Quote Requests (Cases) Section -->
        <div class="mt-5">
          <h2 class="cp-page-title h3 mb-1">My <span class="text-gradient-blue">Quote Requests</span></h2>
          <p class="cp-page-sub mb-4">View status of your submitted cases, review pricing, and book your vendor</p>

          <div class="d-flex flex-column gap-3">
            <?php if (empty($customer_cases)): ?>
              <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                <i class="bi bi-chat-quote fs-1 text-muted"></i>
                <p class="text-muted mt-3 mb-0">You have not submitted any quote requests yet.</p>
              </div>
            <?php else: ?>
              <?php foreach ($customer_cases as $cc):
                  $case_status = $cc['status'];
                  $badge_class = 'cp-badge-outline';
                  if ($case_status === 'Pending') $badge_class = 'bg-warning text-dark';
                  elseif ($case_status === 'Quoted') $badge_class = 'bg-info text-dark';
                  elseif ($case_status === 'Booked') $badge_class = 'bg-success text-white';
                  elseif ($case_status === 'Declined') $badge_class = 'bg-danger text-white';

                  $price_formatted = !empty($cc['service_price']) ? htmlspecialchars($cc['service_currency'] . ' ' . number_format($cc['service_price'], 2)) : 'Price on request';
              ?>
                <div class="card border-0 shadow-sm p-4 bg-white rounded-4 app-item">
                  <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="font-serif h5 mb-0 text-dark app-service-name"><?= htmlspecialchars($cc['service_title']) ?></h4>
                        <span class="badge rounded-pill <?= $badge_class ?>" style="font-size: 0.75rem; padding: 0.35em 0.65em;"><?= $case_status ?></span>
                      </div>
                      <div class="small text-muted app-vendor-name">Vendor: <strong><?= htmlspecialchars($cc['provider_name']) ?></strong></div>
                      <div class="small text-muted">Submitted: <?= date('M d, Y', strtotime($cc['created_at'])) ?></div>
                    </div>

                    <div class="col-md-3">
                      <div class="small text-muted mb-1">Price Quote</div>
                      <span class="font-mono fw-bold text-dark fs-5"><?= $price_formatted ?></span>
                    </div>

                    <div class="col-md-4 text-md-end">
                      <?php if ($case_status === 'Quoted'): ?>
                        <a href="checkout.php?case_id=<?= htmlspecialchars($cc['uuid']) ?>" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                          <i class="bi bi-credit-card-2-front me-1"></i> Book This Vendor
                        </a>
                      <?php elseif ($case_status === 'Booked'): ?>
                        <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill"></i> Secured in Escrow</span>
                      <?php elseif ($case_status === 'Declined'): ?>
                        <span class="text-muted small">Declined by Vendor</span>
                      <?php else: ?>
                        <span class="text-secondary small"><i class="bi bi-hourglass-split"></i> Awaiting Vendor Quote</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="mt-3 p-3 bg-light rounded-3 small text-secondary">
                    <strong>My Requirements:</strong> <?= nl2br(htmlspecialchars($cc['customer_message'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
  <script>
    function filterSearch() {
      const q = document.getElementById('appSearch').value.toLowerCase();
      const items = document.querySelectorAll('.app-item');
      items.forEach(item => {
        const sName = item.querySelector('.app-service-name').textContent.toLowerCase();
        const vName = item.querySelector('.app-vendor-name').textContent.toLowerCase();
        if (sName.includes(q) || vName.includes(q)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
