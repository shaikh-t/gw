<?php
// vendor/quote-requests.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/notifications_helper.php';
require_once __DIR__ . '/../lib/csrf.php';

require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) {
    die("No provider account found for this user.");
}
$provider = provider_find($providers[0]['uuid']);
$provider_id = (int)$provider['id'];

$success_message = '';
$error_message = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $case_uuid = trim($_POST['case_uuid'] ?? '');
    $new_status = trim($_POST['status'] ?? '');

    // Validate status is allowed manually
    $allowed_manual_statuses = ['Pending', 'Quoted', 'Declined'];
    if (!in_array($new_status, $allowed_manual_statuses)) {
        $error_message = "Invalid status update. 'Booked' status can only be achieved via client payment.";
    } else {
        // Fetch case to verify ownership
        $stmt_c = $mysqli->prepare("SELECT * FROM `cases` WHERE uuid = ? AND provider_id = ? LIMIT 1");
        $stmt_c->bind_param('si', $case_uuid, $provider_id);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();
        $case_data = $res_c->fetch_assoc();
        $stmt_c->close();

        if (!$case_data) {
            $error_message = "Case not found or access denied.";
        } else if ($case_data['status'] === 'Booked') {
            $error_message = "This case is already 'Booked' (paid) and its status cannot be modified.";
        } else {
            // Perform update
            $stmt_u = $mysqli->prepare("UPDATE `cases` SET status = ? WHERE id = ?");
            $stmt_u->bind_param('si', $new_status, $case_data['id']);
            if ($stmt_u->execute()) {
                $success_message = "Quote request status updated to '$new_status' successfully!";

                // Fetch service name for notification
                $stmt_s = $mysqli->prepare("SELECT title FROM services WHERE id = ? LIMIT 1");
                $stmt_s->bind_param('i', $case_data['service_id']);
                $stmt_s->execute();
                $res_s = $stmt_s->get_result();
                $service_title = ($row_s = $res_s->fetch_assoc()) ? $row_s['title'] : 'Service';
                $stmt_s->close();

                // Trigger real-time notification to the customer
                $customer_notif_title = "Quote Request Updated";
                $customer_notif_msg = "Your quote request for '$service_title' has been updated to '$new_status' by {$provider['name']}.";

                // Clicking this notification directs customer to payments/applications
                $customer_target_url = "customer/index.php";
                notify_customer($case_data['customer_user_id'], $customer_notif_title, $customer_notif_msg, $customer_target_url);

            } else {
                $error_message = "Failed to update status: " . $mysqli->error;
            }
            $stmt_u->close();
        }
    }
}

// Fetch cases for this vendor
$cases_sql = "SELECT c.*, u.name as customer_name, u.email as customer_email,
                     s.title as service_title, s.price as service_price, s.currency as service_currency, s.uuid as service_uuid
              FROM `cases` c
              JOIN `users` u ON u.id = c.customer_user_id
              JOIN `services` s ON s.id = c.service_id
              WHERE c.provider_id = ?
              ORDER BY c.created_at DESC";
$stmt = $mysqli->prepare($cases_sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res_cases = $stmt->get_result();
$cases = [];
while ($row = $res_cases->fetch_assoc()) {
    $cases[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quote Requests — GlobalWays Vendor</title>
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
          <div><div class="text-white font-serif small">GlobalWays</div><div class="font-mono text-uppercase" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.4)">Vendor Portal</div></div>
        </a>
        <button class="btn btn-link text-white-50 p-0 d-lg-none" data-sidebar-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3 mb-2" style="background:rgba(255,255,255,.05)">
          <?php if($provider['logo']): ?>
            <img src="..<?= htmlspecialchars($provider['logo']) ?>" class="avatar-circle border border-primary" style="width:32px;height:32px;object-fit:cover;">
          <?php else: ?>
            <span class="avatar-circle border border-primary" style="background:rgba(17,101,239,.2);color:#70A5F7"><?= strtoupper(substr($provider['name'], 0, 2)) ?></span>
          <?php endif; ?>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($provider['name']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="services.php"><i class="bi bi-box-seam"></i> Services</a>
        <a class="nav-link" href="quotations.php"><i class="bi bi-file-earmark-richtext"></i> Quotations</a>
        <a class="nav-link active" href="quote-requests.php"><i class="bi bi-chat-quote"></i> Quote Requests</a>
        <a class="nav-link" href="cases.php"><i class="bi bi-briefcase"></i> Cases <span class="badge rounded-pill">0</span></a>
        <a class="nav-link" href="crm.php"><i class="bi bi-people"></i> CRM</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
        <a class="nav-link" href="team.php"><i class="bi bi-person-badge"></i> My Team</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
          <h1 class="h5 mb-0">Quote Requests</h1>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-light"><i class="bi bi-bell"></i></button>
          <span class="avatar-circle bg-dark"><?= strtoupper(substr($user['name'], 0, 2)) ?></span>
        </div>
      </header>

      <main class="p-4 p-lg-5">
        <div class="mb-4">
          <h2 class="font-serif h3 mb-1">Incoming Quote Requests</h2>
          <p class="text-muted mb-0">Review requests from customers, provide price quotes, and manage their status.</p>
        </div>

        <?php if ($success_message !== ''): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if ($error_message !== ''): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Customer</th>
                  <th>Requested Service</th>
                  <th>Price Details</th>
                  <th>Customer Message</th>
                  <th>Date Requested</th>
                  <th>Status</th>
                  <th class="pe-4 text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($cases)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="bi bi-chat-quote fs-1"></i>
                      <p class="mt-3 mb-0">No quote requests received yet.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($cases as $c):
                      $price_text = !empty($c['service_price']) ? htmlspecialchars($c['service_currency'] . ' ' . number_format($c['service_price'], 2)) : 'Price on request';
                      $status = $c['status'];
                      $status_badge_class = 'bg-secondary';
                      if ($status === 'Pending') $status_badge_class = 'bg-warning text-dark';
                      elseif ($status === 'Quoted') $status_badge_class = 'bg-info text-dark';
                      elseif ($status === 'Booked') $status_badge_class = 'bg-success';
                      elseif ($status === 'Declined') $status_badge_class = 'bg-danger';
                  ?>
                    <tr>
                      <td class="ps-4">
                        <div class="fw-semibold"><?= htmlspecialchars($c['customer_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($c['customer_email']) ?></div>
                      </td>
                      <td>
                        <strong><?= htmlspecialchars($c['service_title']) ?></strong>
                      </td>
                      <td>
                        <span class="font-mono text-dark"><?= $price_text ?></span>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($c['customer_message']) ?>">
                          <?= htmlspecialchars($c['customer_message']) ?>
                        </div>
                      </td>
                      <td>
                        <span class="small text-muted"><?= date('M d, Y h:i A', strtotime($c['created_at'])) ?></span>
                      </td>
                      <td>
                        <span class="badge rounded-pill <?= $status_badge_class ?>"><?= $status ?></span>
                      </td>
                      <td class="pe-4 text-end">
                        <?php if ($status === 'Booked'): ?>
                          <span class="small text-success"><i class="bi bi-shield-check"></i> Booked via Payment</span>
                        <?php else: ?>
                          <form action="quote-requests.php" method="post" class="d-inline-flex gap-1 align-items-center">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="case_uuid" value="<?= htmlspecialchars($c['uuid']) ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;" required>
                              <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                              <option value="Quoted" <?= $status === 'Quoted' ? 'selected' : '' ?>>Quoted</option>
                              <option value="Declined" <?= $status === 'Declined' ? 'selected' : '' ?>>Declined</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-gw-dark">Update</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
