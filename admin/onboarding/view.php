<?php
// admin/onboarding/view.php
require_once __DIR__ . '/../../lib/middleware.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/users_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';
// session_start();
require_permission_or_die('providers.manage');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['flash_errors'] = 'Onboarding entry id is required.';
    header('Location: ' . $domain . '/admin/provider_overview.php');
    exit;
}

// Fetch onboarding entry
$oq = $mysqli->query("SELECT * FROM onboarding_queue WHERE id = " . $id . " LIMIT 1");
if (!$oq || $oq->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Onboarding entry not found.';
    header('Location: ' . $domain . '/admin/provider_overview.php');
    exit;
}
$entry = $oq->fetch_assoc();
$oq->free();

// Fetch provider
$prov = $mysqli->query("SELECT * FROM providers WHERE id = " . intval($entry['provider_id']) . " LIMIT 1");
$provider = $prov ? $prov->fetch_assoc() : null;
if ($prov) $prov->free();

// CSRF token
if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));

// Handle POST actions (approve/reject/needs_info/in_review)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'], $token)) {
        $_SESSION['flash_errors'] = 'Invalid CSRF token.';
        header('Location: ' . $domain . '/admin/onboarding/view.php?id=' . $id);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $notes = $mysqli->real_escape_string(trim($_POST['notes'] ?? ''));
    $assigned_user = isset($_POST['assigned_user_id']) ? intval($_POST['assigned_user_id']) : null;
    $now = date('Y-m-d H:i:s');

    if (!in_array($action, ['in_review','needs_info','approved','rejected'])) {
        $_SESSION['flash_errors'] = 'Invalid action.';
        header('Location: ' . $domain . '/admin/onboarding/view.php?id=' . $id);
        exit;
    }

    // Update onboarding_queue
    $assigned_sql = $assigned_user ? intval($assigned_user) : 'NULL';
    $mysqli->query("UPDATE onboarding_queue SET status = '".$mysqli->real_escape_string($action)."', assigned_user_id = $assigned_sql, notes = '".$mysqli->real_escape_string($notes)."', updated_at = NOW(), processed_at = ".($action === 'approved' || $action === 'rejected' ? "NOW()" : "NULL")." WHERE id = ".intval($id));

    // Update provider verification_status when approved/rejected
    if ($action === 'approved') {
        $mysqli->query("UPDATE providers SET verification_status = 'approved', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
    } elseif ($action === 'rejected') {
        $mysqli->query("UPDATE providers SET verification_status = 'rejected', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
    } elseif ($action === 'needs_info') {
        $mysqli->query("UPDATE providers SET verification_status = 'needs_info', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
    } else {
        // in_review
        $mysqli->query("UPDATE providers SET verification_status = 'pending', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
    }

    // Audit log
    $actor = intval(current_user()['id']);
    $note = $mysqli->real_escape_string("Onboarding action: $action; notes: $notes");
    $mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note) VALUES ($actor, 'onboarding_$action', 'provider', ".intval($entry['provider_id']).", '$note')");

    $_SESSION['flash_success'] = 'Onboarding entry updated.';
    header('Location: ' . $domain . '/admin/onboarding/view.php?id=' . $id);
    exit;
}

// Fetch assigned user info if present
$assigned_user = null;
if (!empty($entry['assigned_user_id'])) {
    $u = $mysqli->query("SELECT id,name,email FROM users WHERE id = " . intval($entry['assigned_user_id']) . " LIMIT 1");
    if ($u && ($ur = $u->fetch_assoc())) $assigned_user = $ur;
    if ($u) $u->free();
}

// Load provider verification docs for display
$verification_docs = [];
if (!empty($provider['verification_docs'])) {
    $verification_docs = json_decode($provider['verification_docs'], true) ?: [];
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4">
  <h3>Onboarding Review</h3>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php
        $errors = $_SESSION['flash_errors'];
        if (is_array($errors)) foreach ($errors as $e) echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>'; else echo htmlspecialchars($errors, ENT_QUOTES);
        unset($_SESSION['flash_errors']);
      ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <h5>Provider</h5>
      <p><strong><?php echo htmlspecialchars($provider['name'] ?? '—', ENT_QUOTES); ?></strong></p>
      <p>Verification status: <strong><?php echo htmlspecialchars($provider['verification_status'] ?? '—', ENT_QUOTES); ?></strong></p>
      <p>Onboarding entry created: <?php echo htmlspecialchars($entry['created_at'], ENT_QUOTES); ?></p>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong>Uploaded Documents</strong></div>
    <div class="card-body">
      <?php if (empty($verification_docs)): ?>
        <div class="text-muted">No documents found.</div>
      <?php else: ?>
        <ul>
          <?php foreach ($verification_docs as $doc): ?>
            <li>
              <?php echo htmlspecialchars($doc['original'] ?? $doc['filename'], ENT_QUOTES); ?>
              <a class="btn btn-sm btn-outline-secondary ms-2" href="<?php echo $domain; ?>/uploads/providers/<?php echo htmlspecialchars($doc['filename'], ENT_QUOTES); ?>" target="_blank">View</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong>Review Actions</strong></div>
    <div class="card-body">
      <form method="post" action="<?php echo $domain; ?>/admin/onboarding/view.php?id=<?php echo intval($id); ?>">
        <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES); ?>">
        <div class="mb-3">
          <label class="form-label">Assign to user (optional)</label>
          <input type="number" name="assigned_user_id" class="form-control" value="<?php echo intval($entry['assigned_user_id'] ?? 0); ?>">
          <div class="form-text">Enter user id to assign this review to a staff member.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($entry['notes'] ?? '', ENT_QUOTES); ?></textarea>
        </div>

        <div class="mb-3">
          <button name="action" value="in_review" class="btn btn-outline-primary">Mark In Review</button>
          <button name="action" value="needs_info" class="btn btn-warning">Request More Info</button>
          <button name="action" value="approved" class="btn btn-success">Approve</button>
          <button name="action" value="rejected" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>

  <a href="<?php echo $domain; ?>/admin/provider_overview.php" class="btn btn-link">Back to Providers</a>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
