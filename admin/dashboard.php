<?php
// admin/dashboard.php
require_once __DIR__ . '/../lib/middleware.php';
require_permission_or_die('dashboard.view');
require_once __DIR__ . '/../lib/users_helpers.php';
require_once __DIR__ . '/../lib/role_helpers.php';

$current = current_user();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container mt-4">

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div id="flashSuccess" class="alert alert-success">
      <?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div id="flashErrors" class="alert alert-danger">
      <?php
        $errors = $_SESSION['flash_errors'];
        if (is_array($errors)) {
            foreach ($errors as $e) {
                echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>';
            }
        } else {
            echo '<div>' . htmlspecialchars($errors, ENT_QUOTES) . '</div>';
        }
        unset($_SESSION['flash_errors']);
      ?>
    </div>
  <?php endif; ?>

  <h2>Admin Dashboard</h2>
  <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES); ?>.</p>

  <?php if (can('cache.clear')): ?>
    <div class="card mt-4 mb-4 border-warning">
      <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">System Utilities</h5>
        <span class="badge bg-dark">RBAC Controlled</span>
      </div>
      <div class="card-body">
        <p class="mb-2 text-muted font-sans text-sm">Manually clear and purge all cached system objects, including APCu, local compiled assets, file fragments, and Redis caches.</p>
        <button id="btnClearCache" class="btn btn-warning text-dark fw-bold">
          <span class="spinner-border spinner-border-sm d-none" id="clearCacheSpinner" role="status" aria-hidden="true"></span>
          Clear Application Cache
        </button>
      </div>
    </div>
  <?php endif; ?>

  <div class="row mt-4">
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h5 class="card-title">Users</h5>
          <p class="card-text">Manage system users and roles.</p>
          <?php if (can('users.manage')): ?>
            <a href="<?php echo $domain;?>/admin/users/index.php" class="btn btn-primary">Go</a>
          <?php else: ?>
            <button class="btn btn-secondary" disabled>Restricted</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h5 class="card-title">Roles</h5>
          <p class="card-text">Define roles and assign permissions.</p>
          <?php if (can('roles.manage')): ?>
            <a href="<?php echo $domain;?>/admin/roles/index.php" class="btn btn-primary">Go</a>
          <?php else: ?>
            <button class="btn btn-secondary" disabled>Restricted</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h5 class="card-title">Permissions</h5>
          <p class="card-text">Manage granular access rights.</p>
          <?php if (can('permissions.manage')): ?>
            <a href="<?php echo $domain;?>/admin/permissions/index.php" class="btn btn-primary">Go</a>
          <?php else: ?>
            <button class="btn btn-secondary" disabled>Restricted</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h5 class="card-title">Reviews</h5>
          <p class="card-text">Moderate and manage user reviews.</p>
          <?php if (can('reviews.manage')): ?>
            <a href="<?php echo $domain;?>/admin/reviews/index.php" class="btn btn-primary">Go</a>
          <?php else: ?>
            <button class="btn btn-secondary" disabled>Restricted</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function showToast(type, message) {
  // Remove any existing dynamic alert first
  var oldAlert = document.getElementById('dynamicCacheAlert');
  if (oldAlert) oldAlert.remove();

  var alertDiv = document.createElement('div');
  alertDiv.id = 'dynamicCacheAlert';
  alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show mt-3';
  alertDiv.setAttribute('role', 'alert');
  alertDiv.innerHTML = '<strong>System Update:</strong> ' + message +
    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

  var container = document.querySelector('.container');
  if (container) {
    container.insertBefore(alertDiv, container.firstChild);
  }
}

function initDashboardScripts() {
  console.log('initDashboardScripts called. readyState:', document.readyState);
  var successBox = document.getElementById('flashSuccess');
  var errorBox = document.getElementById('flashErrors');
  [successBox, errorBox].forEach(function(box) {
    if (box) {
      setTimeout(function() {
        box.classList.add('fade');
        setTimeout(function() { box.style.display = 'none'; }, 500);
      }, 4000);
    }
  });

  var btnClearCache = document.getElementById('btnClearCache');
  console.log('btnClearCache found:', !!btnClearCache);
  if (btnClearCache) {
    if (btnClearCache.dataset.initCache === 'true') return;
    btnClearCache.dataset.initCache = 'true';

    btnClearCache.addEventListener('click', function() {
      console.log('CLEAR CACHE CLICKED!');
      var spinner = document.getElementById('clearCacheSpinner');

      // Show loading spinner and disable button
      if (spinner) spinner.classList.remove('d-none');
      btnClearCache.disabled = true;

      // Make secure AJAX POST request
      fetch('clear_cache_action.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>'
        },
        body: '_csrf=' + encodeURIComponent('<?php echo csrf_token(); ?>')
      })
      .then(function(res) {
        return res.json().then(function(data) {
          if (res.ok && data.status === 'success') {
            showToast('success', data.message || 'Cache cleared successfully.');
          } else {
            showToast('danger', data.message || 'An error occurred.');
          }
        });
      })
      .catch(function(err) {
        showToast('danger', 'Network error or connection lost.');
      })
      .finally(function() {
        if (spinner) spinner.classList.add('d-none');
        btnClearCache.disabled = false;
      });
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDashboardScripts);
} else {
  initDashboardScripts();
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
