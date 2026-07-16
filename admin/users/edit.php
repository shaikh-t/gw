<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/users_helpers.php';
require_once __DIR__ . '/../../lib/role_helpers.php';

// $id = intval($_GET['id'] ?? 0);
$id = $_GET['uuid'] ?? $_GET['id'] ?? '';

$user_new = user_find($id);
if (!$user_new) { http_response_code(404); echo 'Not found'; exit; }
$id=$user_new['id'];
// print_r($user_new);
$roles = roles_all();
// load assigned role ids
$assigned = [];
$res = $mysqli->query("SELECT role_id FROM user_roles WHERE user_id = $id");
if ($res) { while ($r = $res->fetch_assoc()) $assigned[] = (int)$r['role_id']; $res->free(); }

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  
  <h4>Edit user</h4>
  <?php if (!empty($_SESSION['flash_errors'])): ?>
  <div id="flashErrors" class="flash-errors">
    <?php
      $errors = $_SESSION['flash_errors'];
      if (is_array($errors)) {
          foreach ($errors as $e) {
              echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>';
          }
      } else {
          echo '<div>' . htmlspecialchars($errors, ENT_QUOTES) . '</div>';
      }
      // clear after showing
      unset($_SESSION['flash_errors']);
    ?>
  </div>
<?php endif; ?>
  <form method="post" action="<?php echo $domain;?>/admin/users/update.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" value="<?php echo htmlspecialchars($user_new['name'], ENT_QUOTES); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($user_new['email'], ENT_QUOTES); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">New password</label>
      <input name="password" type="password" class="form-control">
      <div class="form-text">Leave blank to keep current password</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Roles</label>
      <select name="roles[]" class="form-select" multiple>
        <?php foreach ($roles as $r): ?>
          <option value="<?php echo intval($r['id']); ?>" <?php echo in_array($r['id'], $assigned) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['label'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Avatar</label>
      <div class="mb-2">
        <img src="<?php echo $domain.htmlspecialchars($user_new['avatar'] ?$user_new['avatar']: '/public/assets/img/avatar-placeholder.png', ENT_QUOTES); ?>" style="width:80px;height:80px;border-radius:8px;">
      </div>
      <input name="avatar" type="file" accept="image/*" class="form-control">
    </div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
