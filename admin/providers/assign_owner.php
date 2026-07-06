<?php
// admin/providers/assign_owner.php
require_once __DIR__ . '/../../lib/middleware.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/users_helpers.php';
require_permission_or_die('providers.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $_POST['_csrf'] ?? '')) {
        $_SESSION['flash_errors'] = 'Invalid CSRF token.';
        header('Location: '.$domain.'/admin/provider_overview.php');
        exit;
    }

    $provider_id = intval($_POST['provider_id'] ?? 0);
    $new_owner_id = intval($_POST['owner_user_id'] ?? 0);

    if ($provider_id <= 0 || $new_owner_id <= 0) {
        $_SESSION['flash_errors'] = 'Provider and owner are required.';
        header('Location: '.$domain.'/admin/provider_overview.php');
        exit;
    }

    // Validate user exists
    $u = $mysqli->query("SELECT id,name,email FROM users WHERE id = $new_owner_id LIMIT 1");
    if (!$u || $u->num_rows === 0) {
        $_SESSION['flash_errors'] = 'Selected user not found.';
        header('Location: '.$domain.'/admin/provider_overview.php');
        exit;
    }
    $user = $u->fetch_assoc();
    if ($u) $u->free();

    // Update provider owner
    $mysqli->query("UPDATE providers SET owner_user_id = ".intval($new_owner_id).", updated_at = NOW() WHERE id = ".intval($provider_id));

    // Audit
    $actor = intval(current_user()['id']);
    $note = $mysqli->real_escape_string("Assigned owner_user_id={$new_owner_id} ({$user['email']}) to provider {$provider_id}");
    $mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note) VALUES ($actor, 'assign_owner', 'provider', ".intval($provider_id).", '$note')");

    $_SESSION['flash_success'] = 'Provider owner updated.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}

// GET: show simple form
$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($provider_id <= 0) {
    $_SESSION['flash_errors'] = 'Provider id is required.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}

$prov = $mysqli->query("SELECT id,name,owner_user_id FROM providers WHERE id = $provider_id LIMIT 1");
if (!$prov || $prov->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Provider not found.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}
$provider = $prov->fetch_assoc();
if ($prov) $prov->free();

if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4">
  <h3>Assign Provider Owner — <?php echo htmlspecialchars($provider['name'], ENT_QUOTES); ?></h3>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php
        $errors = $_SESSION['flash_errors'];
        if (is_array($errors)) foreach ($errors as $e) echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>'; else echo htmlspecialchars($errors, ENT_QUOTES);
        unset($_SESSION['flash_errors']);
      ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo $domain; ?>/admin/providers/assign_owner.php">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES); ?>">
    <input type="hidden" name="provider_id" value="<?php echo intval($provider['id']); ?>">
    <div class="mb-3">
      <label class="form-label">Owner User ID</label>
      <input type="number" name="owner_user_id" class="form-control" value="<?php echo intval($provider['owner_user_id']); ?>" required>
      <div class="form-text">Enter the user id of the new owner. You can look up users in the Users admin page.</div>
    </div>
    <button class="btn btn-primary">Assign Owner</button>
    <a class="btn btn-link" href="<?php echo $domain; ?>/admin/provider_overview.php">Cancel</a>
  </form>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
