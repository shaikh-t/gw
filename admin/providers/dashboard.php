<?php
// admin/provider/dashboard.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

$current = current_user();
$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($provider_id <= 0) {
    $_SESSION['flash_errors'] = 'Provider id is required.';
    header('Location: /admin/provider_overview.php');
    exit;
}

// fetch provider
$provRes = $mysqli->query("SELECT * FROM providers WHERE id = " . $provider_id . " LIMIT 1");
if (!$provRes || $provRes->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Provider not found.';
    header('Location: /admin/provider_overview.php');
    exit;
}
$provider = $provRes->fetch_assoc();
$provRes->free();

// metrics and recent items
$metrics = provider_dashboard_metrics($provider_id);

// recent services (latest 10)
$recentServices = [];
$sq = "SELECT id, title, status, price, updated_at, created_at FROM services WHERE provider_id = " . $provider_id . " ORDER BY updated_at DESC, created_at DESC LIMIT 10";
if ($sr = $mysqli->query($sq)) {
    while ($s = $sr->fetch_assoc()) $recentServices[] = $s;
    $sr->free();
}

// recent onboarding entries
$onboarding = [];
$oq = "SELECT id, status, assigned_user_id, notes, created_at, updated_at FROM onboarding_queue WHERE provider_id = " . $provider_id . " ORDER BY created_at DESC LIMIT 10";
if ($or = $mysqli->query($oq)) {
    while ($o = $or->fetch_assoc()) {
        if (!empty($o['assigned_user_id'])) {
            $u = $mysqli->query("SELECT id,name,email FROM users WHERE id = " . intval($o['assigned_user_id']) . " LIMIT 1");
            if ($u && ($ur = $u->fetch_assoc())) {
                $o['assigned_user'] = $ur;
            }
            if ($u) $u->free();
        }
        $onboarding[] = $o;
    }
    $or->free();
}

// recent reviews (latest 10)
$recentReviews = [];
$rq = "SELECT r.id, r.rating, r.title, r.body, r.status, r.created_at, u.name AS user_name
       FROM reviews r LEFT JOIN users u ON u.id = r.user_id
       WHERE r.provider_id = " . $provider_id . "
       ORDER BY r.created_at DESC LIMIT 10";
if ($rr = $mysqli->query($rq)) {
    while ($rv = $rr->fetch_assoc()) $recentReviews[] = $rv;
    $rr->free();
}

// flash handling
$flashErrors = $_SESSION['flash_errors'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_errors'], $_SESSION['flash_success']);

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
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
    <h3>Provider Admin Dashboard — <?php echo htmlspecialchars($provider['name'], ENT_QUOTES); ?></h3>
    <div>
      <a href="<?php echo $domain;?>/admin/providers/edit.php?uuid=<?php echo htmlspecialchars($provider['uuid']); ?>" class="btn btn-outline-secondary btn-sm">Edit Provider</a>
      <a href="<?php echo $domain;?>/admin/reviews/index.php?provider_id=<?php echo intval($provider_id); ?>" class="btn btn-outline-primary btn-sm">All Reviews</a>
      <a href="<?php echo $domain;?>/admin/providers/export.php?id=<?php echo intval($provider_id); ?>" class="btn btn-outline-success btn-sm">Export Report</a>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted small">Total services</div>
        <div class="h4"><?php echo intval($metrics['total_services']); ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted small">Published services</div>
        <div class="h4"><?php echo intval($metrics['published_services']); ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted small">Average rating</div>
        <div class="h4"><?php echo $metrics['avg_rating'] !== null ? htmlspecialchars($metrics['avg_rating']) : '—'; ?></div>
        <div class="text-muted small"><?php echo intval($metrics['rating_count']); ?> reviews</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted small">Pending reviews</div>
        <div class="h4"><?php echo intval($metrics['pending_reviews']); ?></div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Recent services -->
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header"><strong>Recent Services</strong></div>
        <div class="card-body p-0">
          <?php if (empty($recentServices)): ?>
            <div class="p-3 text-muted">No services found.</div>
          <?php else: ?>
            <table class="table mb-0">
              <thead>
                <tr><th>Title</th><th>Status</th><th>Price</th><th>Updated</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($recentServices as $s): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($s['status'], ENT_QUOTES); ?></td>
                    <td><?php echo $s['price'] !== null ? htmlspecialchars($s['price'], ENT_QUOTES) : '—'; ?></td>
                    <td><?php echo htmlspecialchars($s['updated_at'] ?? $s['created_at'], ENT_QUOTES); ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary" href="<?php echo $domain;?>/admin/services/edit.php?uuid=<?php echo htmlspecialchars($s['uuid']); ?>">Edit</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><strong>Onboarding Queue</strong></div>
        <div class="card-body p-0">
          <?php if (empty($onboarding)): ?>
            <div class="p-3 text-muted">No onboarding entries.</div>
          <?php else: ?>
            <table class="table mb-0">
              <thead><tr><th>Status</th><th>Assigned</th><th>Notes</th><th>Created</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($onboarding as $o): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($o['status'], ENT_QUOTES); ?></td>
                    <td><?php echo !empty($o['assigned_user']) ? htmlspecialchars($o['assigned_user']['name'], ENT_QUOTES) : '<span class="text-muted">—</span>'; ?></td>
                    <td><?php echo $o['notes'] ? htmlspecialchars(mb_strimwidth($o['notes'], 0, 80, '...'), ENT_QUOTES) : '<span class="text-muted">—</span>'; ?></td>
                    <td><?php echo htmlspecialchars($o['created_at'], ENT_QUOTES); ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-secondary" href="<?php echo $domain;?>/admin/onboarding/view.php?id=<?php echo intval($o['id']); ?>">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent reviews -->
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Recent Reviews</strong>
          <small class="text-muted">Latest 10</small>
        </div>
        <div class="card-body">
          <?php if (empty($recentReviews)): ?>
            <div class="text-muted">No reviews yet.</div>
          <?php else: ?>
            <ul class="list-unstyled">
              <?php foreach ($recentReviews as $rv): ?>
                <li class="mb-3">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong><?php echo intval($rv['rating']); ?>★</strong>
                      <?php echo htmlspecialchars($rv['title'] ?: mb_strimwidth($rv['body'], 0, 80, '...'), ENT_QUOTES); ?>
                      <div class="text-muted small"><?php echo htmlspecialchars($rv['user_name'] ?? 'Anonymous', ENT_QUOTES); ?> — <?php echo htmlspecialchars($rv['created_at'], ENT_QUOTES); ?></div>
                    </div>
                    <div class="text-end">
                      <a class="btn btn-sm btn-outline-primary" href="<?php echo $domain;?>/admin/reviews/view.php?id=<?php echo intval($rv['id']); ?>">View</a>
                      <a class="btn btn-sm btn-outline-danger" href="<?php echo $domain;?>/admin/reviews/delete.php?id=<?php echo intval($rv['id']); ?>" onclick="return confirm('Delete this review?');">Delete</a>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><strong>Quick Actions</strong></div>
        <div class="card-body">
          <a href="<?php echo $domain;?>/admin/services/create.php?provider_id=<?php echo intval($provider_id); ?>" class="btn btn-primary mb-2">Create Service</a>
          <a href="<?php echo $domain;?>/admin/providers/impersonate.php?id=<?php echo intval($provider_id); ?>" class="btn btn-outline-secondary mb-2">Impersonate Provider</a>
          <a href="<?php echo $domain;?>/admin/providers/export.php?id=<?php echo intval($provider_id); ?>" class="btn btn-outline-success mb-2">Export CSV</a>
        </div>
      </div>
    </div>
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

<?php include __DIR__ . '/../../partials/footer.php'; ?>
