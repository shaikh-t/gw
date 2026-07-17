<?php
// admin/crm/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.view');
require_once __DIR__ . '/../../lib/users_helpers.php';
require_once __DIR__ . '/../../lib/role_helpers.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Count total matching customer accounts (role viewer) where deleted_at IS NULL
if ($search !== '') {
    $stmt_count = $mysqli->prepare("SELECT COUNT(DISTINCT u.id) as total
                                    FROM users u
                                    JOIN user_roles ur ON ur.user_id = u.id
                                    JOIN roles r ON r.id = ur.role_id
                                    WHERE r.name = 'viewer' AND u.deleted_at IS NULL
                                    AND (u.name LIKE ? OR u.email LIKE ?)");
    $search_param = '%' . $search . '%';
    $stmt_count->bind_param('ss', $search_param, $search_param);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();
    $total = $res_count->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();

    // Fetch paginated customer records
    $stmt_users = $mysqli->prepare("SELECT DISTINCT u.*
                                    FROM users u
                                    JOIN user_roles ur ON ur.user_id = u.id
                                    JOIN roles r ON r.id = ur.role_id
                                    WHERE r.name = 'viewer' AND u.deleted_at IS NULL
                                    AND (u.name LIKE ? OR u.email LIKE ?)
                                    ORDER BY u.id DESC LIMIT ? OFFSET ?");
    $stmt_users->bind_param('ssii', $search_param, $search_param, $perPage, $offset);
    $stmt_users->execute();
    $users_res = $stmt_users->get_result();
    $customers = [];
    while ($row = $users_res->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt_users->close();
} else {
    $res_count = $mysqli->query("SELECT COUNT(DISTINCT u.id) as total
                                 FROM users u
                                 JOIN user_roles ur ON ur.user_id = u.id
                                 JOIN roles r ON r.id = ur.role_id
                                 WHERE r.name = 'viewer' AND u.deleted_at IS NULL");
    $total = $res_count->fetch_assoc()['total'] ?? 0;

    $stmt_users = $mysqli->prepare("SELECT DISTINCT u.*
                                    FROM users u
                                    JOIN user_roles ur ON ur.user_id = u.id
                                    JOIN roles r ON r.id = ur.role_id
                                    WHERE r.name = 'viewer' AND u.deleted_at IS NULL
                                    ORDER BY u.id DESC LIMIT ? OFFSET ?");
    $stmt_users->bind_param('ii', $perPage, $offset);
    $stmt_users->execute();
    $users_res = $stmt_users->get_result();
    $customers = [];
    while ($row = $users_res->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt_users->close();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container-fluid mt-2">
  <div class="card shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-people-fill text-primary"></i> Customer CRM</h2>
        <p class="text-muted small mb-0">Manage and track unified customer activities, timelines, and accounts.</p>
      </div>
      <?php if (can('users.manage')): ?>
        <a href="<?php echo $domain;?>/admin/crm/create.php" class="btn btn-primary d-flex align-items-center gap-1">
          <i class="bi bi-person-plus-fill"></i> Add New Customer
        </a>
      <?php endif; ?>
    </div>

    <!-- Search bar -->
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-6 col-lg-4">
        <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search customer by name or email..." value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
          <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
          <?php if ($search !== ''): ?>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_errors'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($_SESSION['flash_errors'], ENT_QUOTES); unset($_SESSION['flash_errors']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Avatar</th>
            <th>Name</th>
            <th>Email</th>
            <th>Nationality</th>
            <th>Emirate</th>
            <th>Created At</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2"></i> No active customers found matching criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($customers as $cust): ?>
              <tr>
                <td>
                  <img src="<?php echo htmlspecialchars($cust['avatar'] ?: '/public/assets/img/avatar-placeholder.png', ENT_QUOTES); ?>" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">
                </td>
                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($cust['name'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($cust['email'], ENT_QUOTES); ?></td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cust['nationality'] ?: 'N/A', ENT_QUOTES); ?></span></td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cust['emirate'] ?: 'N/A', ENT_QUOTES); ?></span></td>
                <td><span class="small text-muted"><?php echo htmlspecialchars($cust['created_at'], ENT_QUOTES); ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="view.php?uuid=<?php echo htmlspecialchars($cust['uuid']); ?>" class="btn btn-outline-info" title="View Timeline Activity">
                      <i class="bi bi-clock-history"></i> Activity
                    </a>
                    <a href="edit.php?uuid=<?php echo htmlspecialchars($cust['uuid']); ?>" class="btn btn-outline-secondary" title="Edit Customer Details">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php if (can('users.manage')): ?>
                      <form method="post" action="delete.php" class="d-inline-block" onsubmit="return confirm('Are you sure you want to soft-delete this customer profile?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($cust['uuid']); ?>">
                        <button class="btn btn-outline-danger" type="submit" title="Soft Delete Profile"><i class="bi bi-trash"></i> Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php
      $pages = (int)ceil($total / $perPage);
      if ($pages > 1):
    ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

</main>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
