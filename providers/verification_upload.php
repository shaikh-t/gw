<?php
// provider/verification_upload.php
require_once __DIR__ . '/../lib/middleware.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/users_helpers.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
session_start();

$current = current_user();
if (!$current) {
    $_SESSION['flash_errors'] = 'You must be signed in to upload verification documents.';
    header('Location: ' . $domain . '/login.php');
    exit;
}

// CSRF token
if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'], $token)) {
        $_SESSION['flash_errors'] = 'Invalid CSRF token.';
        header('Location: ' . $domain . '/provider/verification_upload.php?id=' . intval($_POST['provider_id'] ?? 0));
        exit;
    }

    $provider_id = intval($_POST['provider_id'] ?? 0);
    if ($provider_id <= 0) {
        $_SESSION['flash_errors'] = 'Provider id is required.';
        header('Location: ' . $domain . '/provider/dashboard.php');
        exit;
    }

    // Fetch provider and check ownership or admin
    $pRes = $mysqli->query("SELECT * FROM providers WHERE id = " . $provider_id . " LIMIT 1");
    if (!$pRes || $pRes->num_rows === 0) {
        $_SESSION['flash_errors'] = 'Provider not found.';
        header('Location: ' . $domain . '/provider/dashboard.php');
        exit;
    }
    $provider = $pRes->fetch_assoc();
    $pRes->free();

    $is_owner = (!empty($provider['owner_user_id']) && intval($provider['owner_user_id']) === intval($current['id']));
    if (!$is_owner && !user_has_permission($current['id'], 'providers.manage')) {
        $_SESSION['flash_errors'] = 'You do not have permission to upload verification documents for this provider.';
        header('Location: ' . $domain . '/provider/dashboard.php');
        exit;
    }

    // Validate files exist
    if (empty($_FILES['verification_files'])) {
        $_SESSION['flash_errors'] = 'No files uploaded.';
        header('Location: ' . $domain . '/provider/verification_upload.php?id=' . $provider_id);
        exit;
    }

    // Process uploads
    $saved = [];
    $uploadDir = __DIR__ . '/../uploads/providers/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Normalize to array of files
    $files = [];
    if (is_array($_FILES['verification_files']['name'])) {
        $count = count($_FILES['verification_files']['name']);
        for ($i = 0; $i < $count; $i++) {
            $files[] = [
                'name' => $_FILES['verification_files']['name'][$i],
                'type' => $_FILES['verification_files']['type'][$i],
                'tmp_name' => $_FILES['verification_files']['tmp_name'][$i],
                'error' => $_FILES['verification_files']['error'][$i],
                'size' => $_FILES['verification_files']['size'][$i],
            ];
        }
    } else {
        $files[] = $_FILES['verification_files'];
    }

    foreach ($files as $f) {
        if ($f['error'] !== UPLOAD_ERR_OK) continue;
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $safe = bin2hex(random_bytes(12)) . ($ext ? '.' . preg_replace('/[^a-z0-9]/i', '', $ext) : '');
        $dest = $uploadDir . $safe;
        if (!move_uploaded_file($f['tmp_name'], $dest)) continue;
        $saved[] = [
            'filename' => $safe,
            'original' => $f['name'],
            'uploaded_at' => date('c'),
            'size' => intval($f['size'])
        ];
    }

    if (empty($saved)) {
        $_SESSION['flash_errors'] = 'No files were saved. Please try again.';
        header('Location: ' . $domain . '/provider/verification_upload.php?id=' . $provider_id);
        exit;
    }

    // Merge into existing verification_docs JSON
    $res = $mysqli->query("SELECT verification_docs FROM providers WHERE id = " . intval($provider_id) . " LIMIT 1");
    $existing = [];
    if ($res && ($row = $res->fetch_assoc())) {
        $existing = json_decode($row['verification_docs'] ?? '[]', true) ?: [];
        $res->free();
    }
    $merged = array_merge($existing, $saved);
    $meta = $mysqli->real_escape_string(json_encode($merged));
    $mysqli->query("UPDATE providers SET verification_docs = '$meta', verification_status = 'pending', updated_at = NOW() WHERE id = " . intval($provider_id));

    // Add onboarding queue entry
    $notes = $mysqli->real_escape_string('Provider uploaded verification docs: ' . implode(',', array_column($saved, 'filename')));
    $mysqli->query("INSERT INTO onboarding_queue (provider_id, status, notes, created_at) VALUES (".intval($provider_id).", 'pending', '$notes', NOW())");

    // Audit log
    $actor = intval($current['id']);
    $note = $mysqli->real_escape_string("Uploaded verification docs: " . implode(',', array_column($saved, 'filename')));
    $mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note) VALUES ($actor, 'upload_verification', 'provider', ".intval($provider_id).", '$note')");

    $_SESSION['flash_success'] = 'Verification documents uploaded successfully and queued for review.';
    header('Location: ' . $domain . '/provider/dashboard.php');
    exit;
}

// GET: show upload form
$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container mt-4">
  <h3>Upload Verification Documents</h3>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php
        $errors = $_SESSION['flash_errors'];
        if (is_array($errors)) foreach ($errors as $e) echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>'; else echo htmlspecialchars($errors, ENT_QUOTES);
        unset($_SESSION['flash_errors']);
      ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" action="<?php echo $domain; ?>/provider/verification_upload.php">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES); ?>">
    <input type="hidden" name="provider_id" value="<?php echo intval($provider_id); ?>">
    <div class="mb-3">
      <label class="form-label">Select documents (ID, business registration, certificates)</label>
      <input type="file" name="verification_files[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png">
      <div class="form-text">PDF or image files. Max recommended size 10MB each.</div>
    </div>
    <button class="btn btn-primary">Upload and Submit for Review</button>
    <a class="btn btn-link" href="<?php echo $domain; ?>/provider/dashboard.php">Cancel</a>
  </form>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
