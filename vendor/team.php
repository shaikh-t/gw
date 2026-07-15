<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/uuid_helper.php';
require_once __DIR__ . '/../lib/upload.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);

$success = null;
$error = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $action = $_POST['action_type'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');

        if ($name === '' || $role === '') {
            $error = "Name and Role are required.";
        } else {
            $avatarPath = null;
            if (!empty($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                $destDir = __DIR__ . '/../public/uploads/team_avatars';
                $resUpload = file_upload_handle($_FILES['avatar_file'], $destDir, 2 * 1024 * 1024, false);
                if ($resUpload['ok']) {
                    $avatarPath = '/public/uploads/team_avatars/' . $resUpload['filename'];
                } else {
                    $error = "Avatar upload failed: " . $resUpload['error'];
                }
            }

            if (!$error) {
                $memberUuid = generate_uuid();
                $pid = intval($provider['id']);
                $stmt = $mysqli->prepare("INSERT INTO provider_team_members (uuid, provider_id, name, role, specialties, avatar) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sissss', $memberUuid, $pid, $name, $role, $specialties, $avatarPath);
                if ($stmt->execute()) {
                    $success = "Team member added successfully.";
                } else {
                    $error = "Failed to add team member: " . $mysqli->error;
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'edit') {
        $uuid_val = $_POST['uuid'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');

        if ($uuid_val === '' || $name === '' || $role === '') {
            $error = "Name, Role, and Member reference are required.";
        } else {
            $member = provider_team_member_find($uuid_val);
            if (!$member || intval($member['provider_id']) !== intval($provider['id'])) {
                $error = "Invalid team member.";
            } else {
                $avatarPath = $member['avatar'];
                if (!empty($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                    $destDir = __DIR__ . '/../public/uploads/team_avatars';
                    $resUpload = file_upload_handle($_FILES['avatar_file'], $destDir, 2 * 1024 * 1024, false);
                    if ($resUpload['ok']) {
                        $avatarPath = '/public/uploads/team_avatars/' . $resUpload['filename'];
                    } else {
                        $error = "Avatar upload failed: " . $resUpload['error'];
                    }
                }

                if (!$error) {
                    $stmt = $mysqli->prepare("UPDATE provider_team_members SET name = ?, role = ?, specialties = ?, avatar = ? WHERE uuid = ?");
                    $stmt->bind_param('sssss', $name, $role, $specialties, $avatarPath, $uuid_val);
                    if ($stmt->execute()) {
                        $success = "Team member updated successfully.";
                    } else {
                        $error = "Failed to update team member: " . $mysqli->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete') {
        $uuid_val = $_POST['uuid'] ?? '';
        if ($uuid_val === '') {
            $error = "Invalid request.";
        } else {
            $member = provider_team_member_find($uuid_val);
            if (!$member || intval($member['provider_id']) !== intval($provider['id'])) {
                $error = "Invalid team member.";
            } else {
                $stmt = $mysqli->prepare("DELETE FROM provider_team_members WHERE uuid = ?");
                $stmt->bind_param('s', $uuid_val);
                if ($stmt->execute()) {
                    $success = "Team member removed successfully.";
                } else {
                    $error = "Failed to remove team member: " . $mysqli->error;
                }
                $stmt->close();
            }
        }
    }
}

$team = provider_team_members_find_by_provider($provider['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Team — GlobalWays Vendor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/globalways.css" rel="stylesheet">
</head>
<body class="bg-warm">
  <div class="dashboard-wrapper d-flex">
    <aside class="dashboard-sidebar d-flex flex-column">
      <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
        <a href="../index.php" class="text-decoration-none d-flex align-items-center gap-2">
          <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:linear-gradient(135deg,#1165EF,#3F83F4)"><i class="bi bi-globe2 text-white small"></i></div>
          <div><div class="text-white font-serif small">GlobalWays</div><div class="font-mono text-uppercase" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.4)">Vendor Portal</div></div>
        </a>
        <button class="btn btn-link text-white-50 p-0 d-lg-none" data-sidebar-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3 mb-2" style="background:rgba(255,255,255,.05)">
          <?php if($provider['logo']): ?>
            <img src="..<?= htmlspecialchars($provider['logo']) ?>" class="avatar-circle border border-primary" style="width:32px;height:32px;object-fit:cover;">
          <?php else: ?>
            <span class="avatar-circle border border-primary" style="background:rgba(17,101,239,.2);color:#70A5F7"><?= strtoupper(substr($provider['name'], 0, 2)) ?></span>
          <?php endif; ?>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($provider['name']) ?></div><div class="font-mono text-uppercase" style="font-size:9px;color:#70A5F7"><?= htmlspecialchars(ucfirst($provider['verification_status'] ?? 'Partner')) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="services.php"><i class="bi bi-box-seam"></i> Services</a>
        <a class="nav-link" href="quotations.php"><i class="bi bi-file-earmark-richtext"></i> Quotations</a>
        <a class="nav-link" href="quote-requests.php"><i class="bi bi-chat-quote"></i> Quote Requests</a>
        <a class="nav-link" href="cases.php"><i class="bi bi-briefcase"></i> Cases <span class="badge rounded-pill">0</span></a>
        <a class="nav-link" href="crm.php"><i class="bi bi-people"></i> CRM</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
        <a class="nav-link active" href="team.php"><i class="bi bi-person-badge"></i> My Team</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button></div>
        <div class="d-flex align-items-center gap-2"><button class="btn btn-light"><i class="bi bi-bell"></i></button><span class="avatar-circle bg-dark"><?= strtoupper(substr($user['name'], 0, 2)) ?></span></div>
      </header>
      <main class="p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="font-serif h2 mb-1">My Team</h1>
            <p class="text-muted mb-0">Manage the specialists displayed on your public profile</p>
          </div>
          <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addMemberModal"><i class="bi bi-plus-lg me-1"></i> Add Member</button>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($success) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
          <?php if (empty($team)): ?>
            <div class="col-12">
              <div class="card border-0 shadow-sm p-5 text-center text-muted">
                <i class="bi bi-people fs-1 mb-3 text-secondary"></i>
                <h2 class="h5 font-serif mb-2 text-dark">No Team Members Added</h2>
                <p class="small mb-3">Add members of your organization to build trust with customers visiting your profile.</p>
                <button class="btn btn-outline-primary rounded-pill btn-sm d-inline-flex align-items-center gap-1 mx-auto" data-bs-toggle="modal" data-bs-target="#addMemberModal"><i class="bi bi-plus"></i> Add First Member</button>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($team as $m):
                // Generate initials
                $words = explode(' ', $m['name']);
                $initials = '';
                foreach ($words as $w) $initials .= mb_substr($w, 0, 1);
                $initials = mb_strtoupper(mb_substr($initials, 0, 2));
            ?>
              <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                  <div class="d-flex justify-content-center mb-3">
                    <?php if ($m['avatar']): ?>
                      <img src="..<?= htmlspecialchars($m['avatar']) ?>" class="avatar-circle border border-light shadow-sm" style="width:72px;height:72px;object-fit:cover;">
                    <?php else: ?>
                      <span class="avatar-circle border border-primary d-flex align-items-center justify-content-center fw-bold" style="width:72px;height:72px;background:rgba(17,101,239,.1);color:#1165EF;font-size:1.25rem;"><?= htmlspecialchars($initials) ?></span>
                    <?php endif; ?>
                  </div>
                  <h3 class="font-serif h5 mb-1"><?= htmlspecialchars($m['name']) ?></h3>
                  <div class="text-primary small fw-semibold mb-2"><?= htmlspecialchars($m['role']) ?></div>
                  <p class="text-muted small mb-4" style="min-height: 40px;"><?= htmlspecialchars($m['specialties'] ?? 'No specialties specified.') ?></p>

                  <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-sm btn-outline-secondary flex-grow-1"
                            data-bs-toggle="modal"
                            data-bs-target="#editMemberModal"
                            data-uuid="<?= htmlspecialchars($m['uuid']) ?>"
                            data-name="<?= htmlspecialchars($m['name']) ?>"
                            data-role="<?= htmlspecialchars($m['role']) ?>"
                            data-specialties="<?= htmlspecialchars($m['specialties'] ?? '') ?>">
                      Edit
                    </button>
                    <form method="POST" class="d-inline-block flex-grow-1" onsubmit="return confirm('Remove this team member?');">
                      <?= csrf_field(); ?>
                      <input type="hidden" name="action_type" value="delete">
                      <input type="hidden" name="uuid" value="<?= htmlspecialchars($m['uuid']) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <!-- Add Member Modal -->
  <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow-lg">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field(); ?>
          <input type="hidden" name="action_type" value="add">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title font-serif">Add Team Member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-4">
            <div class="mb-3">
              <label class="form-label small">Name *</label>
              <input type="text" name="name" class="form-control" placeholder="Mohammed Al-Rashid" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Role / Title *</label>
              <input type="text" name="role" class="form-control" placeholder="Founder & CEO" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Specialties</label>
              <textarea name="specialties" class="form-control" rows="2" placeholder="Immigration Law, Business Setup"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label small">Avatar Photo</label>
              <input type="file" name="avatar_file" class="form-control" accept="image/*">
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary rounded-pill px-4">Add Member</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Member Modal -->
  <div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow-lg">
        <form method="POST" enctype="multipart/form-data" id="editMemberForm">
          <?= csrf_field(); ?>
          <input type="hidden" name="action_type" value="edit">
          <input type="hidden" name="uuid" id="editMemberUuid">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title font-serif">Edit Team Member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-4">
            <div class="mb-3">
              <label class="form-label small">Name *</label>
              <input type="text" name="name" id="editMemberName" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Role / Title *</label>
              <input type="text" name="role" id="editMemberRole" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Specialties</label>
              <textarea name="specialties" id="editMemberSpecialties" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label small">Change Avatar Photo</label>
              <input type="file" name="avatar_file" class="form-control" accept="image/*">
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
  <script>
    const editModal = document.getElementById('editMemberModal');
    if (editModal) {
      editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const uuid = button.getAttribute('data-uuid');
        const name = button.getAttribute('data-name');
        const role = button.getAttribute('data-role');
        const specialties = button.getAttribute('data-specialties');

        document.getElementById('editMemberUuid').value = uuid;
        document.getElementById('editMemberName').value = name;
        document.getElementById('editMemberRole').value = role;
        document.getElementById('editMemberSpecialties').value = specialties;
      });
    }
  </script>
</body>
</html>
