<?php
// admin/providers/edit.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

$id_val = $_GET['uuid'] ?? $_GET['id'] ?? '';
$provider = provider_find($id_val);
if (!$provider) { http_response_code(404); echo 'Provider not found'; exit; }

// owner list for assignment
$users = [];
if (can('users.view')) {
    $sql = "SELECT id, name, email FROM users ORDER BY name LIMIT 200";
    if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $users[] = $r; $res->free(); }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Edit provider</h4>
  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger"><?php if (isset($_SESSION['flash_errors'])) foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>'; unset($_SESSION['flash_errors']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <form method="post" action="<?php echo $domain;?>/admin/providers/update.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($provider['uuid'] ?? $provider['id']); ?>">

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="<?php echo htmlspecialchars($provider['name'], ENT_QUOTES); ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Owner (optional)</label>
        <select name="owner_user_id" class="form-select">
          <option value="">-- none --</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo intval($u['id']); ?>" <?php echo ($provider['owner_user_id'] == $u['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($u['name'] . ' <' . $u['email'] . '>', ENT_QUOTES); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($provider['email'] ?? '', ENT_QUOTES); ?>">
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="<?php echo htmlspecialchars($provider['phone'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="col-md-8 mb-3">
        <label class="form-label">Address</label>
        <input name="address" class="form-control" value="<?php echo htmlspecialchars($provider['address'] ?? '', ENT_QUOTES); ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">City</label>
        <input name="city" class="form-control" value="<?php echo htmlspecialchars($provider['city'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">State</label>
        <input name="state" class="form-control" value="<?php echo htmlspecialchars($provider['state'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Country</label>
        <input name="country" class="form-control" value="<?php echo htmlspecialchars($provider['country'] ?? '', ENT_QUOTES); ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Latitude</label>
        <input name="latitude" class="form-control" value="<?php echo htmlspecialchars($provider['latitude'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Longitude</label>
        <input name="longitude" class="form-control" value="<?php echo htmlspecialchars($provider['longitude'] ?? '', ENT_QUOTES); ?>">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($provider['description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Logo</label>
      <div class="mb-2">
        <img src="<?php echo $domain.htmlspecialchars($provider['logo'] ?: '/public/assets/img/provider-placeholder.png', ENT_QUOTES); ?>" style="width:96px;object-fit:contain;border-radius:6px;">
      </div>
      <input name="logo" type="file" accept="image/*" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?php if($provider['status']==='draft') echo 'selected'; ?>>Draft</option>
        <option value="active" <?php if($provider['status']==='active') echo 'selected'; ?>>Active</option>
        <option value="inactive" <?php if($provider['status']==='inactive') echo 'selected'; ?>>Inactive</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Verification status</label>
      <select name="verification_status" class="form-select">
        <option value="unverified" <?php if($provider['verification_status']==='unverified') echo 'selected'; ?>>Unverified</option>
        <option value="pending" <?php if($provider['verification_status']==='pending') echo 'selected'; ?>>Pending</option>
        <option value="verified" <?php if($provider['verification_status']==='verified') echo 'selected'; ?>>Verified</option>
        <option value="rejected" <?php if($provider['verification_status']==='rejected') echo 'selected'; ?>>Rejected</option>
      </select>
    </div>

    <button class="btn btn-primary">Save changes</button>
    <a href="/admin/providers/index.php" class="btn btn-link">Back</a>
  </form>

  <hr>

  <h5>Structured Verification Documents</h5>
  <?php
    $structured_docs = provider_documents_find_by_provider($provider['id']);
    if (!empty($structured_docs)):
  ?>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Document Title</th>
            <th>File Link</th>
            <th>Uploaded At</th>
            <th>Verification Status</th>
            <th>Show on Frontend</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($structured_docs as $sd): ?>
            <tr>
              <td class="text-start"><strong><?php echo htmlspecialchars($sd['title'], ENT_QUOTES); ?></strong></td>
              <td>
                <a href="<?php echo htmlspecialchars($domain . $sd['file_path'], ENT_QUOTES); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-file-earmark-arrow-down"></i> View / Download
                </a>
              </td>
              <td><?php echo htmlspecialchars($sd['created_at'], ENT_QUOTES); ?></td>
              <td>
                <form method="post" action="<?php echo $domain; ?>/admin/providers/verify_document.php" class="d-inline-flex gap-1 align-items-center">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="doc_uuid" value="<?php echo htmlspecialchars($sd['uuid'], ENT_QUOTES); ?>">
                  <input type="hidden" name="action" value="status">
                  <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="pending" <?php if ($sd['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="verified" <?php if ($sd['status'] === 'verified') echo 'selected'; ?>>Verified</option>
                    <option value="rejected" <?php if ($sd['status'] === 'rejected') echo 'selected'; ?>>Rejected</option>
                  </select>
                </form>
              </td>
              <td>
                <form method="post" action="<?php echo $domain; ?>/admin/providers/verify_document.php" class="d-inline-flex gap-1 align-items-center">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="doc_uuid" value="<?php echo htmlspecialchars($sd['uuid'], ENT_QUOTES); ?>">
                  <input type="hidden" name="action" value="toggle_frontend">
                  <select name="show_on_frontend" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="0" <?php if ($sd['show_on_frontend'] == 0) echo 'selected'; ?>>No</option>
                    <option value="1" <?php if ($sd['show_on_frontend'] == 1) echo 'selected'; ?>>Yes (Show on Profile)</option>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-muted">No structured documents uploaded by this provider.</p>
  <?php endif; ?>

  <hr>

  <h5>Verification documents (Legacy)</h5>
  <?php
    $docs = json_decode($provider['verification_docs'] ?? '[]', true) ?: [];
    if (!empty($docs)):
  ?>
    <ul>
      <?php foreach ($docs as $d): ?>
        <li><a href="<?php echo htmlspecialchars($d, ENT_QUOTES); ?>" target="_blank"><?php echo htmlspecialchars(basename($d), ENT_QUOTES); ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No legacy documents uploaded.</p>
  <?php endif; ?>

  <hr>

  <h5>Admin verification actions</h5>
  <form method="post" action="<?php echo $domain;?>/admin/providers/verify.php" class="mb-3">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($provider['uuid'] ?? $provider['id']); ?>">
    <div class="mb-3">
      <label class="form-label">Action</label>
      <select name="action" class="form-select" required>
        <option value="">-- choose --</option>
        <option value="admin_approved">Approve</option>
        <option value="admin_rejected">Reject</option>
        <option value="admin_requested_more">Request more info</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Note (optional)</label>
      <textarea name="note" class="form-control" rows="3"></textarea>
    </div>
    <button class="btn btn-success">Submit</button>
  </form>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
