<?php
// providers/view.php
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/role_helpers.php';
require_once __DIR__ . '/../lib/auth.php';
session_start();

$slug = $_GET['slug'] ?? '';
if ($slug === '') { http_response_code(404); echo 'Not found'; exit; }

$provider = provider_find($slug);
if (!$provider) { http_response_code(404); echo 'Provider not found'; exit; }

include __DIR__ . '/../partials/header.php';
?>
<div class="container mt-4">
  <div class="row">
    <div class="col-md-8">
      <div class="card p-3 mb-3">
        <div class="d-flex align-items-center">
          <img src="<?php echo htmlspecialchars($provider['logo'] ?: '/public/assets/img/provider-placeholder.png', ENT_QUOTES); ?>" style="width:120px;height:120px;object-fit:cover;border-radius:8px;margin-right:16px;">
          <div>
            <h2 class="mb-1"><?php echo htmlspecialchars($provider['name'], ENT_QUOTES); ?></h2>
            <div class="text-muted"><?php echo htmlspecialchars(implode(', ', array_filter([$provider['city'],$provider['state'],$provider['country']])), ENT_QUOTES); ?></div>
            <div class="mt-2">
              <?php if ($provider['verification_status'] === 'verified'): ?>
                <span class="badge bg-success">Verified</span>
              <?php elseif ($provider['verification_status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark">Verification pending</span>
              <?php else: ?>
                <span class="badge bg-secondary">Unverified</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <hr>

        <div class="mb-3">
          <h5>About</h5>
          <p><?php echo nl2br(htmlspecialchars($provider['description'] ?? '', ENT_QUOTES)); ?></p>
        </div>

        <div class="mb-3">
          <h5>Contact</h5>
          <?php if (!empty($provider['email'])): ?><div>Email: <a href="mailto:<?php echo htmlspecialchars($provider['email'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($provider['email'], ENT_QUOTES); ?></a></div><?php endif; ?>
          <?php if (!empty($provider['phone'])): ?><div>Phone: <?php echo htmlspecialchars($provider['phone'], ENT_QUOTES); ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <h5>Services</h5>
          <p class="text-muted">Services listing will appear here (coming soon).</p>
        </div>

        <div class="mb-3">
          <a href="/book?provider=<?php echo intval($provider['id']); ?>" class="btn btn-primary">Book now</a>
          <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['id'] == $provider['owner_user_id'] || can('providers.manage'))): ?>
            <a href="/admin/providers/edit.php?id=<?php echo intval($provider['id']); ?>" class="btn btn-outline-secondary">Edit provider</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card p-3">
        <h5>Reviews</h5>
        <p class="text-muted">Reviews will be shown here (integration with reviews module).</p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 mb-3">
        <h6>Location</h6>
        <?php if (!empty($provider['latitude']) && !empty($provider['longitude'])): ?>
          <div id="provider-map" style="width:100%;height:220px;background:#eee;"></div>
          <script>
            // Minimal map placeholder. Replace with real map integration (Leaflet/Google Maps) in production.
            document.addEventListener('DOMContentLoaded', function () {
              var el = document.getElementById('provider-map');
              el.innerHTML = '<div style="padding:16px;color:#666;">Lat: <?php echo htmlspecialchars($provider['latitude'], ENT_QUOTES); ?>, Lng: <?php echo htmlspecialchars($provider['longitude'], ENT_QUOTES); ?></div>';
            });
          </script>
        <?php else: ?>
          <p class="text-muted">Location not provided.</p>
        <?php endif; ?>
      </div>

      <div class="card p-3">
        <h6>Share</h6>
        <div>
          <a class="btn btn-outline-secondary btn-sm" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank">Share</a>
          <a class="btn btn-outline-secondary btn-sm" href="mailto:?subject=<?php echo rawurlencode($provider['name']); ?>&body=<?php echo rawurlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">Email</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>


<!-- // provider/view.php (snippet)
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/users_helpers.php';
$provider = $mysqli->query("SELECT * FROM providers WHERE id = " . intval($_GET['id']) . " LIMIT 1")->fetch_assoc();
$services = $mysqli->query("SELECT id,title,price,status FROM services WHERE provider_id = ".intval($provider['id'])." AND status='published' ORDER BY created_at DESC");
?>
<h1><?php echo htmlspecialchars($provider['name']); ?></h1>
<p><?php echo nl2br(htmlspecialchars($provider['description'])); ?></p>
<?php if (user_has_permission($current['id'],'providers.manage') || $provider['owner_user_id']==$current['id']): ?>
  <a class="btn btn-sm btn-outline-primary" href="/admin/provider/dashboard.php?id=<?php echo intval($provider['id']); ?>">Manage</a>
<?php endif; ?>
<ul>
<?php while ($s = $services->fetch_assoc()): ?>
  <li><?php echo htmlspecialchars($s['title']); ?> — <?php echo htmlspecialchars($s['price'] ?? '—'); ?></li>
<?php endwhile; ?>
</ul> -->
