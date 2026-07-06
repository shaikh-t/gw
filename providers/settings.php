<?php
// provider/settings.php
// Provider settings UI — allows provider owner or admins to edit provider info and link to verification upload.

require_once __DIR__ . '/../lib/middleware.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/users_helpers.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
session_start();

$current = current_user();
if (!$current) {
    $_SESSION['flash_errors'] = 'You must be signed in to access provider settings.';
    header('Location: /login.php');
    exit;
}

// CSRF helpers (fallback if not defined elsewhere)
if (!function_exists('csrf_field')) {
    function csrf_field() {
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES) . '">';
    }
}

// provider id from query
$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($provider_id <= 0) {
    $_SESSION['flash_errors'] = 'Provider id is required.';
    header('Location: /provider/dashboard.php');
    exit;
}

// fetch provider
$provRes = $mysqli->query("SELECT * FROM providers WHERE id = " . $provider_id . " LIMIT 1");
if (!$provRes || $provRes->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Provider not found.';
    header('Location: /provider/dashboard.php');
    exit;
}
$provider = $provRes->fetch_assoc();
$provRes->free();

// permission: owner or admin with providers.manage
$is_owner = (!empty($provider['owner_user_id']) && intval($provider['owner_user_id']) === intval($current['id']));
if (!$is_owner && !user_has_permission($current['id'], 'providers.manage')) {
    $_SESSION['flash_errors'] = 'You do not have permission to edit this provider.';
    header('Location: /provider/dashboard.php');
    exit;
}

// prepare values for form
$name = $provider['name'] ?? '';
$phone = $provider['phone'] ?? '';
$description = $provider['description'] ?? '';
$settings = json_decode($provider['settings'] ?? '{}', true) ?: [];
$notify_email = !empty($settings['notify_email']) ? 1 : 0;
$verification_status = $provider['verification_status'] ?? 'unsubmitted';
$verification_docs = json_decode($provider['verification_docs'] ?? '[]', true) ?: [];

// flash messages
$flashErrors = $_SESSION['flash_errors'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_errors'], $_SESSION['flash_success']);

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container mt-4">
  <?php if ($flashSuccess): ?>
    <div id="flashSuccess" class="alert alert-success"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES); ?></div>
  <?php endif; ?>

  <?php if ($flashErrors): ?>
    <div id="flashErrors" class="alert alert-danger">
      <?php
        if (is_array($flashErrors)) {
            foreach ($flashErrors as $e) echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>';
        } else {
            echo '<div>' . htmlspecialchars($flashErrors, ENT_QUOTES) . '</div>';
        }
      ?>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Provider Settings — <?php echo htmlspecialchars($name, ENT_QUOTES); ?></h3>
    <div>
      <?php if ($is_owner || user_has_permission($current['id'], 'providers.manage')): ?>
        <a class="btn btn-outline-secondary btn-sm" href="/provider/verification_upload.php?id=<?php echo intval($provider_id); ?>">Upload Verification</a>
      <?php endif; ?>
      <?php if (user_has_permission($current['id'], 'providers.manage')): ?>
        <a class="btn btn-outline-primary btn-sm" href="/admin/provider/dashboard.php?id=<?php echo intval($provider_id); ?>">Admin View</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card mb-3">
        <div class="card-header"><strong>Edit Provider</strong></div>
        <div class="card-body">
          <form method="post" action="/provider/settings_save.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="provider_id" value="<?php echo intval($provider_id); ?>">

            <div class="mb-3">
              <label class="form-label">Provider Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone, ENT_QUOTES); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($description, ENT_QUOTES); ?></textarea>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="notify_email" id="notify_email" value="1" <?php echo $notify_email ? 'checked' : ''; ?>>
              <label class="form-check-label" for="notify_email">Receive email notifications for provider events</label>
            </div>

            <div class="mb-3">
              <button class="btn btn-primary">Save settings</button>
              <a class="btn btn-link" href="/provider/dashboard.php">Cancel</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><strong>Verification</strong></div>
        <div class="card-body">
          <p>
            <strong>Status:</strong>
            <?php
              $status_label = htmlspecialchars(ucfirst($verification_status), ENT_QUOTES);
              if ($verification_status === 'approved') {
                  echo '<span class="badge bg-success">' . $status_label . '</span>';
              } elseif ($verification_status === 'pending' || $verification_status === 'in_review') {
                  echo '<span class="badge bg-warning text-dark">' . $status_label . '</span>';
              } elseif ($verification_status === 'rejected' || $verification_status === 'needs_info') {
                  echo '<span class="badge bg-danger">' . $status_label . '</span>';
              } else {
                  echo '<span class="badge bg-secondary">' . $status_label . '</span>';
              }
            ?>
          </p>

          <p class="mb-2">
            <?php if (!empty($verification_docs)): ?>
              <strong>Uploaded documents</strong>
              <ul>
                <?php foreach ($verification_docs as $doc): ?>
                  <li>
                    <?php echo htmlspecialchars($doc['original'] ?? $doc['filename'], ENT_QUOTES); ?>
                    <a class="btn btn-sm btn-outline-secondary ms-2" href="/uploads/providers/<?php echo htmlspecialchars($doc['filename'], ENT_QUOTES); ?>" target="_blank">View</a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <span class="text-muted">No verification documents uploaded yet.</span>
            <?php endif; ?>
          </p>

          <div>
            <?php if ($is_owner || user_has_permission($current['id'], 'providers.manage')): ?>
              <a class="btn btn-outline-primary" href="/provider/verification_upload.php?id=<?php echo intval($provider_id); ?>">Upload / Add Documents</a>
            <?php endif; ?>
            <?php if (user_has_permission($current['id'], 'providers.manage')): ?>
              <a class="btn btn-outline-secondary" href="/admin/onboarding/view.php?id=<?php echo intval($provider_id); ?>">View Onboarding Queue</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>

    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header"><strong>Owner</strong></div>
        <div class="card-body">
          <?php
            if (!empty($provider['owner_user_id'])) {
                $uRes = $mysqli->query("SELECT id,name,email FROM users WHERE id = " . intval($provider['owner_user_id']) . " LIMIT 1");
                if ($uRes && ($uRow = $uRes->fetch_assoc())) {
                    echo '<div><strong>' . htmlspecialchars($uRow['name'], ENT_QUOTES) . '</strong></div>';
                    echo '<div class="text-muted small">' . htmlspecialchars($uRow['email'], ENT_QUOTES) . '</div>';
                } else {
                    echo '<div class="text-muted">Owner not found</div>';
                }
                if ($uRes) $uRes->free();
            } else {
                echo '<div class="text-muted">No owner assigned</div>';
            }
          ?>
          <?php if (user_has_permission($current['id'], 'providers.manage')): ?>
            <div class="mt-3">
              <a class="btn btn-sm btn-outline-primary" href="/admin/providers/assign_owner.php?id=<?php echo intval($provider_id); ?>">Assign / Change Owner</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><strong>Quick Links</strong></div>
        <div class="card-body">
          <a class="d-block mb-2" href="/provider/dashboard.php">Provider Dashboard</a>
          <a class="d-block mb-2" href="/provider/services.php?provider_id=<?php echo intval($provider_id); ?>">Manage Services</a>
          <a class="d-block mb-2" href="/provider/reviews.php?provider_id=<?php echo intval($provider_id); ?>">View Reviews</a>
          <?php if (user_has_permission($current['id'], 'providers.manage')): ?>
            <a class="d-block text-danger" href="/admin/providers/delete.php?id=<?php echo intval($provider_id); ?>" onclick="return confirm('Delete provider and all related data?');">Delete Provider</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var s = document.getElementById('flashSuccess');
  var e = document.getElementById('flashErrors');
  [s, e].forEach(function(el){
    if (!el) return;
    setTimeout(function(){ el.style.display = 'none'; }, 4000);
  });
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
