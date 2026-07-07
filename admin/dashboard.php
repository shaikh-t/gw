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
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
