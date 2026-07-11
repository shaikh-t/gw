<?php
// customer/documents.php
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

// Handle upload action
$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'upload') {
        $docUuid = $_POST['doc_uuid'] ?? '';
        $fileName = $_FILES['doc_file']['name'] ?? '';
        if ($docUuid && $fileName) {
            if (upload_customer_document($userId, $docUuid, $fileName)) {
                $flash_success = 'Document uploaded and verified successfully!';
            } else {
                $flash_error = 'Failed to upload document.';
            }
        } else {
            $flash_error = 'Please select a valid file to upload.';
        }
    }
}

// Fetch dynamic data
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
$docs_uploaded = 0;
$docs_needed = 0;
foreach ($docs as $d) {
    if ($d['status'] === 'Verified' || $d['status'] === 'Uploaded' || $d['status'] === 'Expiring Soon') {
        $docs_uploaded++;
    } else {
        $docs_needed++;
    }
}

// Generate initials
$initials = '';
$words = explode(' ', $user['name'] ?? 'Customer');
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documents — GlobalWays Customer</title>
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
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($user['name']) ?></div><div class="font-mono text-truncate" style="font-size:10px;color:rgba(255,255,255,.4)"><?= htmlspecialchars($user['email']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
        <a class="nav-link active" href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
        <a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages <span class="badge rounded-pill"><?= $unread_msgs_count ?></span></a>
        <a class="nav-link" href="payments.php"><i class="bi bi-credit-card"></i> Payments</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
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
        <h1 class="cp-page-title">Document <span class="text-gradient-blue">Vault</span></h1>
        <p class="cp-page-sub">Securely store and manage your documents</p>

        <?php if (!empty($flash_success)): ?>
          <div class="alert alert-success mt-3 mb-3"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
          <div class="alert alert-danger mt-3 mb-3"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <div class="cp-doc-stats">
          <div class="cp-doc-stat">
            <div class="cp-doc-stat-left">
              <span class="cp-doc-stat-icon"><i class="bi bi-file-earmark"></i></span>
              <span class="cp-doc-stat-label">Documents Uploaded</span>
            </div>
            <span class="cp-doc-stat-value"><?= $docs_uploaded ?></span>
          </div>
          <div class="cp-doc-stat">
            <div class="cp-doc-stat-left">
              <span class="cp-doc-stat-icon warn"><i class="bi bi-exclamation-circle"></i></span>
              <span class="cp-doc-stat-label">Documents Needed</span>
            </div>
            <span class="cp-doc-stat-value warn"><?= $docs_needed ?></span>
          </div>
          <div class="cp-doc-stat">
            <div class="cp-doc-stat-left">
              <span class="cp-doc-stat-icon"><i class="bi bi-cloud-download"></i></span>
              <span class="cp-doc-stat-label">Total Storage</span>
            </div>
            <span class="cp-doc-stat-value">1347.3 MB</span>
          </div>
        </div>

        <div class="cp-doc-toolbar">
          <div class="cp-filters">
            <button class="cp-filter active" type="button" onclick="filterDocs('all')">All Documents</button>
            <button class="cp-filter" type="button" onclick="filterDocs('Passport')">Passport &amp; ID</button>
            <button class="cp-filter" type="button" onclick="filterDocs('Bank')">Financial</button>
            <button class="cp-filter" type="button" onclick="filterDocs('Medical')">Certificates</button>
          </div>
        </div>

        <div id="docsContainer">
          <?php foreach ($docs as $d): ?>
            <div class="cp-doc-card doc-card-item" data-name="<?= htmlspecialchars($d['name']) ?>">
              <span class="cp-doc-icon">
                <i class="bi <?= strtolower($d['file_type']) === 'jpg' || strtolower($d['file_type']) === 'png' ? 'bi-file-earmark-image' : 'bi-file-earmark-text' ?>"></i>
              </span>
              <div class="cp-doc-body">
                <div class="cp-doc-head">
                  <h3><?= htmlspecialchars($d['name']) ?></h3>
                  <span class="cp-badge <?= $d['status'] === 'Verified' ? 'cp-badge-green' : ($d['status'] === 'Required' ? 'cp-badge-orange' : 'cp-badge-red') ?>">
                    <?php if ($d['status'] === 'Verified'): ?>
                      <i class="bi bi-check-circle me-1"></i>Verified
                    <?php else: ?>
                      <?= htmlspecialchars($d['status']) ?>
                    <?php endif; ?>
                  </span>
                </div>
                <div class="cp-doc-grid">
                  <div><div class="cp-meta-label">Uploaded</div><div class="cp-meta-value"><?= $d['uploaded_at'] !== '0000-00-00' ? date('M j, Y', strtotime($d['uploaded_at'])) : 'Not uploaded' ?></div></div>
                  <div><div class="cp-meta-label">Expires</div><div class="cp-meta-value"><?= htmlspecialchars($d['expires_at']) ?></div></div>
                  <div><div class="cp-meta-label">Type</div><div class="cp-meta-value"><?= htmlspecialchars($d['file_type']) ?></div></div>
                  <div><div class="cp-meta-label">Size</div><div class="cp-meta-value"><?= htmlspecialchars($d['file_size']) ?></div></div>
                </div>
                <?php if (!empty($d['tags'])): ?>
                  <div class="cp-doc-tags">
                    <?php foreach (explode(',', $d['tags']) as $tag): ?>
                      <span class="cp-tag"><?= htmlspecialchars(trim($tag)) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="cp-doc-actions">
                <?php if ($d['status'] === 'Verified' || $d['status'] === 'Expiring Soon'): ?>
                  <button class="cp-icon-btn" type="button" aria-label="View"><i class="bi bi-eye"></i></button>
                  <button class="cp-icon-btn" type="button" aria-label="Download"><i class="bi bi-download"></i></button>
                  <button class="cp-icon-btn danger" type="button" aria-label="Delete"><i class="bi bi-trash"></i></button>
                <?php else: ?>
                  <form method="post" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="doc_uuid" value="<?= $d['uuid'] ?>">
                    <input type="file" name="doc_file" class="form-control form-control-sm" required>
                    <button class="cp-btn-dark" type="submit"><i class="bi bi-upload"></i> Upload</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="cp-checklist">
          <h3>Smart Document Checklist</h3>
          <p>AI-assisted verification across your active applications</p>
          <div class="cp-check-item"><i class="bi bi-check-circle-fill ok"></i> All passport documents are up to date</div>
          <div class="cp-check-item"><i class="bi bi-check-circle-fill ok"></i> Financial documents verified</div>
          <?php if ($docs_needed > 0): ?>
            <div class="cp-check-item"><i class="bi bi-exclamation-circle-fill warn"></i> Medical report required for Golden Visa</div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
  <script>
    function filterDocs(cat) {
      // Toggle active filter button
      const buttons = document.querySelectorAll('.cp-doc-toolbar .cp-filter');
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');

      const items = document.querySelectorAll('.doc-card-item');
      items.forEach(item => {
        const name = item.getAttribute('data-name');
        if (cat === 'all') {
          item.style.display = 'flex';
        } else if (name.includes(cat)) {
          item.style.display = 'flex';
        } else {
          item.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
