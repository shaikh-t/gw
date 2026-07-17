<?php
// admin/crm/view.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.view');

$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';
$customer = null;

$stmt = $mysqli->prepare("SELECT * FROM users WHERE uuid = ? AND deleted_at IS NULL LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $customer = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$customer) {
    $_SESSION['flash_errors'] = 'Customer profile not found.';
    header('Location: index.php');
    exit;
}

$userId = (int)$customer['id'];

// Multi-stream high-performance UNION timeline query
$timeline_query = "
    (SELECT
       l.created_at AS event_time,
       'Chat Interaction' AS event_type,
       CONCAT('Interacted with AI Assistant (Language: ', UPPER(COALESCE(s.selected_language, 'en')), ')') AS event_description,
       'bi-chat-dots-fill text-primary' AS event_icon
     FROM bot_chat_logs l
     JOIN bot_sessions s ON s.id = l.session_id
     WHERE s.user_id = ?)
    UNION ALL
    (SELECT
       c.created_at AS event_time,
       'Case Application' AS event_type,
       CONCAT('Initialized Case Application (Status: ', c.status, ')') AS event_description,
       'bi-folder-fill text-warning' AS event_icon
     FROM cases c
     WHERE c.customer_user_id = ?)
    UNION ALL
    (SELECT
       t.created_at AS event_time,
       'Payment Transaction' AS event_type,
       CONCAT('Executed Payment Transaction (Amount: ', t.gross_amount, ', Gateway: Stripe_Webhook)') AS event_description,
       'bi-credit-card-fill text-success' AS event_icon
     FROM payment_transactions t
     JOIN cases c ON c.uuid = t.case_uuid
     WHERE c.customer_user_id = ?)
    ORDER BY event_time DESC
";

$timeline = [];
$stmt_time = $mysqli->prepare($timeline_query);
if ($stmt_time) {
    $stmt_time->bind_param('iii', $userId, $userId, $userId);
    $stmt_time->execute();
    $res_time = $stmt_time->get_result();
    if ($res_time) {
        while ($row = $res_time->fetch_assoc()) {
            $timeline[] = $row;
        }
    }
    $stmt_time->close();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container-fluid mt-2">
  <div class="row g-4">
    <!-- Left Column: Customer Profile Details Card -->
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 mb-4 text-center p-4">
        <div class="mb-3">
          <img src="<?php echo htmlspecialchars($customer['avatar'] ?: '/public/assets/img/avatar-placeholder.png', ENT_QUOTES); ?>" class="rounded-circle border p-1" style="width:120px;height:120px;object-fit:cover;">
        </div>
        <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($customer['name'], ENT_QUOTES); ?></h4>
        <p class="text-muted small mb-3"><?php echo htmlspecialchars($customer['email'], ENT_QUOTES); ?></p>

        <div class="d-flex justify-content-center gap-2 mb-4">
          <span class="badge bg-primary px-3 py-2 rounded-pill">Customer Account</span>
          <?php if ($customer['deleted_at'] !== null): ?>
            <span class="badge bg-danger px-3 py-2 rounded-pill">Soft Deleted</span>
          <?php else: ?>
            <span class="badge bg-success px-3 py-2 rounded-pill">Active</span>
          <?php endif; ?>
        </div>

        <div class="border-top pt-3 text-start">
          <div class="row g-2 mb-3">
            <div class="col-6 text-muted small">UUID:</div>
            <div class="col-6 text-dark font-mono small text-truncate" title="<?php echo htmlspecialchars($customer['uuid'], ENT_QUOTES); ?>">
              <?php echo htmlspecialchars($customer['uuid'], ENT_QUOTES); ?>
            </div>

            <div class="col-6 text-muted small">Nationality:</div>
            <div class="col-6 text-dark fw-semibold"><?php echo htmlspecialchars($customer['nationality'] ?: 'Not Provided', ENT_QUOTES); ?></div>

            <div class="col-6 text-muted small">Target Emirate:</div>
            <div class="col-6 text-dark fw-semibold"><?php echo htmlspecialchars($customer['emirate'] ?: 'Not Provided', ENT_QUOTES); ?></div>

            <div class="col-6 text-muted small">Onboarding Goal:</div>
            <div class="col-6 text-dark fw-semibold"><?php echo htmlspecialchars($customer['goal'] ?: 'Not Provided', ENT_QUOTES); ?></div>

            <div class="col-6 text-muted small">Member Since:</div>
            <div class="col-6 text-dark small"><?php echo htmlspecialchars(date('F d, Y', strtotime($customer['created_at'])), ENT_QUOTES); ?></div>
          </div>
        </div>

        <div class="d-grid gap-2 border-top pt-3">
          <a href="edit.php?uuid=<?php echo htmlspecialchars($customer['uuid'], ENT_QUOTES); ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-pencil"></i> Edit Details
          </a>
          <a href="index.php" class="btn btn-dark btn-sm rounded-pill">
            <i class="bi bi-arrow-left"></i> Back to CRM
          </a>
        </div>
      </div>
    </div>

    <!-- Right Column: Interactive Chronological Activity Timeline Feed -->
    <div class="col-lg-8">
      <div class="card shadow-sm border-0 p-4">
        <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-clock-history text-secondary me-1"></i> Customer Activity Timeline</h4>

        <?php if (empty($timeline)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-calendar2-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
            <p class="mb-0">This customer has no recorded platform activity yet.</p>
            <span class="small text-muted">Initialize actions or chat with the bot to see logs here.</span>
          </div>
        <?php else: ?>
          <div class="position-relative ps-4" style="border-left: 2px solid #e9ecef; margin-left: 10px;">
            <?php foreach ($timeline as $event): ?>
              <div class="mb-4 position-relative">
                <!-- Bullet point / Icon -->
                <span class="position-absolute d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="left: -35px; top: 0px; width: 28px; height: 28px; border: 2px solid #e9ecef;">
                  <i class="bi <?php echo htmlspecialchars($event['event_icon'], ENT_QUOTES); ?>" style="font-size: 0.9rem;"></i>
                </span>

                <div class="card border-0 bg-light p-3 rounded-3 shadow-xs">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-dark-subtle text-dark-emphasis text-uppercase small" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                      <?php echo htmlspecialchars($event['event_type'], ENT_QUOTES); ?>
                    </span>
                    <span class="text-muted font-mono" style="font-size: 0.75rem;">
                      <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($event['event_time'])), ENT_QUOTES); ?>
                    </span>
                  </div>
                  <p class="mb-0 text-dark small"><?php echo htmlspecialchars($event['event_description'], ENT_QUOTES); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</main>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
