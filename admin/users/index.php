<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.view');
require_once __DIR__ . '/../../lib/users_helpers.php';
require_once __DIR__ . '/../../lib/role_helpers.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$total = users_count();
$users = users_paginated($page, $perPage);
$roles = roles_all();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
echo '<main class="main-content p-4">';

?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Users</h4>
    <?php if (can('users.manage')): ?>
      <a href="<?php echo $domain;?>/admin/users/create.php" class="btn btn-primary">Create user</a>
    <?php endif; ?>
  </div>

  <table class="table table-hover">
    <thead><tr><th></th><th>Name</th><th>Email</th><th>Roles</th><th>Created</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><img src="<?php echo $domain.htmlspecialchars($u['avatar'] ?: '/public/assets/img/avatar-placeholder.png', ENT_QUOTES); ?>" class="header-avatar" style="width:40px;height:40px;"></td>
          <td><?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?></td>
          <td>
            <?php
              // fetch roles for display
              $res = [];
              $sql = "SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = " . intval($u['id']);
              if ($r = $mysqli->query($sql)) {
                  while ($rw = $r->fetch_assoc()) $res[] = htmlspecialchars($rw['name'], ENT_QUOTES);
                  $r->free();
              }
              echo implode(', ', $res);
            ?>
          </td>
          <td><?php echo htmlspecialchars($u['created_at'], ENT_QUOTES); ?></td>
          <td class="text-end">
            <a href="<?php echo $domain;?>/admin/users/edit.php?id=<?php echo intval($u['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <?php if (can('users.manage')): ?>
              <form method="post" action="<?php echo $domain;?>/admin/users/delete.php" class="d-inline-block" onsubmit="return confirm('Delete user?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo intval($u['id']); ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php
    $pages = (int)ceil($total / $perPage);
    if ($pages > 1):
  ?>
  <nav>
    <ul class="pagination">
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
