<?php
// customer/profile.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/customer_helpers.php';
require_once __DIR__ . '/../lib/csrf.php';

require_login();

// Guard access: customer role only
if (is_role('provider') || is_role('admin') || is_role('Super Admin')) {
    header('Location: ../login.php');
    exit;
}

$user = current_user();
$userId = (int)$user['id'];

// Handle Profile POST Save
$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $goal = trim($_POST['goal'] ?? '');
    $emirate = trim($_POST['emirate'] ?? '');

    if ($name === '') {
        $flash_error = 'Full name is required.';
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET name = ?, phone = ?, nationality = ?, goal = ?, emirate = ? WHERE id = ?");
        $stmt->bind_param('sssssi', $name, $phone, $nationality, $goal, $emirate, $userId);

        if ($stmt->execute()) {
            $flash_success = 'Profile updated successfully!';

            // Sync with session
            $_SESSION['user']['name'] = $name;
            $user = current_user();
        } else {
            $flash_error = 'Failed to update profile: ' . $mysqli->error;
        }
        $stmt->close();
    }
}

// Fetch lists
$apps = get_customer_applications($userId);
$docs = get_customer_documents($userId);
$messages = get_customer_messages($userId);

// Unread messages count
$unread_msgs_count = 0;
foreach ($messages as $m) {
    if ($m['sender'] !== 'You') {
        $unread_msgs_count++;
    }
}

// Stats
$apps_count = count($apps);
$completed_count = 0;
foreach ($apps as $a) {
    if ($a['status'] === 'Completed') $completed_count++;
}
$docs_count = count($docs);

// Fetch fresh details of the user from DB to show updated info
$stmt_u = $mysqli->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt_u->bind_param('i', $userId);
$stmt_u->execute();
$res_u = $stmt_u->get_result();
$db_user = $res_u->fetch_assoc();
$stmt_u->close();

if (!$db_user) {
    $db_user = $user;
}

// Generate initials
$initials = '';
$words = explode(' ', $db_user['name'] ?? 'Customer');
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';

// Member since calculation
$member_since = 'January 2026';
if (!empty($db_user['created_at'])) {
    $member_since = date('F Y', strtotime($db_user['created_at']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile — GlobalWays Customer</title>
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
          <div><div class="text-white font-serif small">GlobalWays</div><div class="font-mono text-uppercase" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.4)">Customer Portal</div></div>
        </a>
        <button class="btn btn-link text-white-50 p-0 d-lg-none" data-sidebar-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:rgba(255,255,255,.05)">
          <span class="avatar-circle bg-dark border border-secondary"><?= htmlspecialchars($initials) ?></span>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($db_user['name']) ?></div><div class="font-mono text-truncate" style="font-size:10px;color:rgba(255,255,255,.4)"><?= htmlspecialchars($db_user['email']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
        <a class="nav-link" href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
        <a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages <span class="badge rounded-pill"><?= $unread_msgs_count ?></span></a>
        <a class="nav-link" href="payments.php"><i class="bi bi-credit-card"></i> Payments</a>
        <a class="nav-link active" href="profile.php"><i class="bi bi-person"></i> Profile</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
          <div class="cp-search d-none d-sm-flex">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search applications, documents..">
          </div>
        </div>
        <div class="cp-top-actions">
          <button class="cp-bell" type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span class="cp-dot"></span></button>
          <span class="avatar-circle bg-dark"><?= htmlspecialchars($initials) ?></span>
        </div>
      </header>
      <main class="cp-page">

        <?php if (!empty($flash_success)): ?>
          <div class="alert alert-success mt-3 mb-3"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
          <div class="alert alert-danger mt-3 mb-3"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <form method="post" action="profile.php">
          <?= csrf_field(); ?>
          <div class="cp-profile-head">
            <div>
              <h1 class="cp-page-title">My <span class="text-gradient-blue">Profile</span></h1>
              <p class="cp-page-sub">Manage your personal information</p>
            </div>
            <button class="cp-btn-dark" type="submit"><i class="bi bi-check-circle me-1"></i> Save Profile</button>
          </div>

          <div class="row g-4">
            <div class="col-lg-8">
              <div class="cp-card">
                <h3>Personal Information</h3>
                <div class="cp-field-grid">
                  <div class="cp-field">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($db_user['name']) ?>" required>
                  </div>
                  <div class="cp-field">
                    <label>Email Address</label>
                    <input type="email" value="<?= htmlspecialchars($db_user['email']) ?>" readonly class="bg-light">
                  </div>
                  <div class="cp-field">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($db_user['phone'] ?? '') ?>">
                  </div>
                  <div class="cp-field">
                    <label>Date Of Birth</label>
                    <input type="text" value="15 March 1990" readonly class="bg-light">
                  </div>
                  <div class="cp-field">
                    <label>Nationality</label>
                    <input type="text" name="nationality" value="<?= htmlspecialchars($db_user['nationality'] ?? '') ?>">
                  </div>
                  <div class="cp-field">
                    <label>Passport Number</label>
                    <input type="text" value="C12345678" readonly class="bg-light">
                  </div>
                </div>
              </div>

              <div class="cp-card">
                <h3>UAE Information</h3>
                <div class="cp-field-grid">
                  <div class="cp-field">
                    <label>Goal</label>
                    <input type="text" name="goal" value="<?= htmlspecialchars($db_user['goal'] ?? '') ?>">
                  </div>
                  <div class="cp-field">
                    <label>Emirate Base</label>
                    <select class="form-select border border-secondary border-opacity-25 rounded-3 py-2" name="emirate">
                      <option <?= ($db_user['emirate'] ?? '') === 'Dubai' ? 'selected' : '' ?>>Dubai</option>
                      <option <?= ($db_user['emirate'] ?? '') === 'Abu Dhabi' ? 'selected' : '' ?>>Abu Dhabi</option>
                      <option <?= ($db_user['emirate'] ?? '') === 'Sharjah' ? 'selected' : '' ?>>Sharjah</option>
                      <option <?= ($db_user['emirate'] ?? '') === 'Ajman' ? 'selected' : '' ?>>Ajman</option>
                      <option <?= ($db_user['emirate'] ?? '') === 'Ras Al Khaimah' ? 'selected' : '' ?>>Ras Al Khaimah</option>
                      <option <?= ($db_user['emirate'] ?? '') === 'Fujairah' ? 'selected' : '' ?>>Fujairah</option>
                      <option <?= ($db_user['emirate'] ?? '') === 'Umm Al Quwain' ? 'selected' : '' ?>>Umm Al Quwain</option>
                    </select>
                  </div>
                  <div class="cp-field" style="grid-column: 1 / -1">
                    <label>Address in UAE</label>
                    <textarea rows="2" readonly class="bg-light">Dubai Marina, Dubai, UAE</textarea>
                  </div>
                </div>
              </div>

              <div class="cp-card">
                <h3>Security Settings</h3>
                <div class="cp-sec-row">
                  <span>Change Password</span>
                  <button class="cp-icon-btn" type="button" aria-label="Edit password"><i class="bi bi-pencil"></i></button>
                </div>
                <div class="cp-sec-row">
                  <span>Two-Factor Authentication</span>
                  <span class="cp-badge cp-badge-blue">Enabled</span>
                </div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="cp-profile-summary">
                <div class="cp-profile-avatar"><?= htmlspecialchars($initials) ?></div>
                <h3><?= htmlspecialchars($db_user['name']) ?></h3>
                <div class="role">Customer Account</div>
                <div class="cp-profile-contact">
                  <div><i class="bi bi-envelope"></i> <?= htmlspecialchars($db_user['email']) ?></div>
                  <div><i class="bi bi-telephone"></i> <?= htmlspecialchars($db_user['phone'] ?? '') ?></div>
                  <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($db_user['emirate'] ?? 'Dubai') ?>, UAE</div>
                  <div><i class="bi bi-calendar3"></i> Member since <?= htmlspecialchars($member_since) ?></div>
                </div>
              </div>

              <div class="cp-card">
                <h3>Account Statistics</h3>
                <div class="cp-stat-list">
                  <div><?= $apps_count ?> Applications Submitted</div>
                  <div><span class="good"><?= $completed_count ?></span> Completed Successfully</div>
                  <div><?= $docs_count ?> Documents Uploaded</div>
                </div>
              </div>

              <div class="cp-card">
                <h3>Preferences</h3>
                <div class="cp-pref-row">
                  <span>Email Notifications</span>
                  <button class="cp-toggle on" type="button" aria-label="Toggle email"></button>
                </div>
                <div class="cp-pref-row">
                  <span>SMS Notifications</span>
                  <button class="cp-toggle on" type="button" aria-label="Toggle SMS"></button>
                </div>
                <div class="cp-pref-row">
                  <span>Marketing Emails</span>
                  <button class="cp-toggle" type="button" aria-label="Toggle marketing"></button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
