<?php
// admin/provider_overview.php
require_once __DIR__ . '/../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/users_helpers.php';

$current = current_user();

// optional filter by provider id or uuid
$provider_id_val = $_GET['uuid'] ?? $_GET['id'] ?? null;

// fetch providers list or single provider
if ($provider_id_val) {
    $providers = [provider_find($provider_id_val)];
} else {
    $providers = provider_summary_list(200);
}

// flash handling (supports string or array)
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
    <h3>Providers Overview</h3>
    <div>
      <a href="<?php echo $domain; ?>/admin/providers/create.php" class="btn btn-primary">Create Provider</a>
      <a href="<?php echo $domain; ?>/admin/providers/export.php" class="btn btn-outline-secondary">Export CSV</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>Provider</th>
          <th>Owner</th>
          <th>Services</th>
          <th>Avg Rating</th>
          <th>Pending Reviews</th>
          <th>Onboarding</th>
          <th>Created</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($providers as $p):
            $pid = intval($p['id']);
            $ownerName = '';
            if (!empty($p['owner_user_id'])) {
                $uRes = $mysqli->query("SELECT name,email FROM users WHERE id = " . intval($p['owner_user_id']) . " LIMIT 1");
                if ($uRes && ($uRow = $uRes->fetch_assoc())) $ownerName = htmlspecialchars($uRow['name'] . ' <' . $uRow['email'] . '>', ENT_QUOTES);
                if ($uRes) $uRes->free();
            }
            $metrics = provider_dashboard_metrics($pid);
            // onboarding status
            $onboarding = 'n/a';
            $oq = $mysqli->query("SELECT status FROM onboarding_queue WHERE provider_id = $pid ORDER BY created_at DESC LIMIT 1");
            if ($oq && ($orow = $oq->fetch_assoc())) $onboarding = htmlspecialchars($orow['status']);
            if ($oq) $oq->free();
        ?>
        <tr>
          <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></td>
          <td><?php echo $ownerName ?: '<span class="text-muted">—</span>'; ?></td>
          <td><?php echo intval($metrics['total_services']); ?></td>
          <td><?php echo $metrics['avg_rating'] !== null ? htmlspecialchars($metrics['avg_rating']) : '—'; ?></td>
          <td><?php echo intval($metrics['pending_reviews']); ?></td>
          <td><?php echo $onboarding; ?></td>
          <td><?php echo htmlspecialchars($p['created_at'] ?? ''); ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $domain; ?>/admin/providers/dashboard.php?uuid=<?php echo htmlspecialchars($p['uuid']); ?>">View</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $domain; ?>/admin/providers/edit.php?uuid=<?php echo htmlspecialchars($p['uuid']); ?>">Edit</a>
            <a class="btn btn-sm btn-outline-info" href="<?php echo $domain; ?>/admin/reviews/index.php?provider_uuid=<?php echo htmlspecialchars($p['uuid']); ?>">Reviews</a>
            <form method="post" action="<?php echo $domain; ?>/admin/providers/delete.php" class="d-inline-block" onsubmit="return confirm('Delete provider?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['uuid']); ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var err = document.getElementById('flashErrors');
  var suc = document.getElementById('flashSuccess');
  [err, suc].forEach(function(el){
    if (!el) return;
    setTimeout(function(){
      el.classList.add('fade-out');
      setTimeout(function(){ el.style.display = 'none'; }, 500);
    }, 4000);
  });
});
</script>

<style>
.fade-out { opacity: 0; transition: opacity 0.5s ease-out; }
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
