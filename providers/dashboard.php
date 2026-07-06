<?php
// provider/dashboard.php
require_once __DIR__ . '/../lib/middleware.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/users_helpers.php';
$current = current_user();

// Determine provider(s) for this user
$providers = providers_for_user($current['id']);
if (empty($providers)) {
    // If user is admin, optionally show all providers
    if (!user_has_permission($current['id'], 'providers.manage')) {
        $_SESSION['flash_errors'] = 'You do not have any providers assigned.';
        header('Location: /'); exit;
    } else {
        $providers = provider_summary_list(100);
    }
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container mt-4">
  <h3>Provider Dashboard</h3>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div id="flashSuccess" class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <?php foreach ($providers as $p): 
      $metrics = provider_dashboard_metrics($p['id']);
  ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></strong>
        <div>
          <a class="btn btn-sm btn-outline-primary" href="/provider/services.php?provider_id=<?php echo intval($p['id']); ?>">Services</a>
          <a class="btn btn-sm btn-outline-secondary" href="/provider/reviews.php?provider_id=<?php echo intval($p['id']); ?>">Reviews</a>
          <?php if (user_has_permission($current['id'], 'providers.manage')): ?>
            <a class="btn btn-sm btn-danger" href="/admin/provider/dashboard.php?id=<?php echo intval($p['id']); ?>">Admin View</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <div><strong>Total services</strong></div>
            <div><?php echo intval($metrics['total_services']); ?></div>
          </div>
          <div class="col-md-3">
            <div><strong>Published</strong></div>
            <div><?php echo intval($metrics['published_services']); ?></div>
          </div>
          <div class="col-md-3">
            <div><strong>Avg rating</strong></div>
            <div><?php echo $metrics['avg_rating'] !== null ? htmlspecialchars($metrics['avg_rating']) : '—'; ?> (<?php echo intval($metrics['rating_count']); ?>)</div>
          </div>
          <div class="col-md-3">
            <div><strong>Pending reviews</strong></div>
            <div><?php echo intval($metrics['pending_reviews']); ?></div>
          </div>
        </div>

        <hr>

        <h6>Recent reviews</h6>
        <?php if (empty($metrics['recent_reviews'])): ?>
          <div class="text-muted">No recent reviews</div>
        <?php else: ?>
          <ul class="list-unstyled">
            <?php foreach ($metrics['recent_reviews'] as $rv): ?>
              <li class="mb-2">
                <strong><?php echo intval($rv['rating']); ?>★</strong>
                <?php echo htmlspecialchars($rv['title'], ENT_QUOTES); ?>
                <div class="text-muted small"><?php echo htmlspecialchars($rv['user_name'] ?? 'Anonymous'); ?> — <?php echo htmlspecialchars($rv['created_at']); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var s = document.getElementById('flashSuccess');
  if (s) setTimeout(function(){ s.style.display='none'; }, 4000);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
