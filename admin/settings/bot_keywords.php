<?php
// admin/settings/bot_keywords.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('manage_bot_keywords');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/cache_helper.php';

if (!isset($cspNonce)) {
    $cspNonce = base64_encode(random_bytes(16));
}

// 1. Process Post Actions (Add/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? '');

    $action = trim($_POST['action'] ?? '');

    if ($action === 'add') {
        $token = mb_strtolower(trim($_POST['keyword_token'] ?? ''), 'UTF-8');
        // Clean punctuation
        $token = preg_replace('/\p{P}/u', '', $token);
        $lang_code = trim($_POST['language_code'] ?? 'en');

        if ($token !== '') {
            $stmt = $mysqli->prepare("INSERT INTO bot_approved_keywords (keyword_token, language_code) VALUES (?, ?) ON DUPLICATE KEY UPDATE language_code = VALUES(language_code)");
            if ($stmt) {
                $stmt->bind_param('ss', $token, $lang_code);
                if ($stmt->execute()) {
                    // Flush cache
                    CacheUtility::delete('bot_approved_keywords_' . $lang_code);
                    $_SESSION['flash_success'] = "Approved keyword '{$token}' added successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to add keyword: " . $mysqli->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['flash_error'] = "Keyword token cannot be empty.";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        // Find keyword first to flush cache
        $token_lang = 'en';
        $stmt_find = $mysqli->prepare("SELECT language_code FROM bot_approved_keywords WHERE id = ? LIMIT 1");
        if ($stmt_find) {
            $stmt_find->bind_param('i', $id);
            $stmt_find->execute();
            $res_find = $stmt_find->get_result();
            if ($row_find = $res_find->fetch_assoc()) {
                $token_lang = $row_find['language_code'];
            }
            $stmt_find->close();
        }

        $stmt = $mysqli->prepare("DELETE FROM bot_approved_keywords WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                // Flush cache
                CacheUtility::delete('bot_approved_keywords_' . $token_lang);
                $_SESSION['flash_success'] = "Approved keyword successfully deleted.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete keyword.";
            }
            $stmt->close();
        }
    }

    header("Location: bot_keywords.php");
    exit;
}

// 2. Retrieve Keywords
$keywords = [];
$res = $mysqli->query("SELECT * FROM bot_approved_keywords ORDER BY keyword_token ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $keywords[] = $row;
    }
    $res->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container-fluid mt-2">
  <div class="card shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-spellcheck text-primary"></i> Database-Driven Typo Spelling Dictionary</h2>
        <p class="text-muted small mb-0">Manage approved system tokens used in real-time Levenshtein distance typo correction checks.</p>
      </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show rounded-3 p-3 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show rounded-3 p-3 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Add Keyword Row -->
    <div class="card bg-light border-0 p-3 mb-4 rounded-3">
      <h5 class="h6 fw-bold text-dark mb-3"><i class="bi bi-plus-circle me-1"></i> Register New System Keyword Token</h5>
      <form method="post" action="bot_keywords.php" id="addKeywordForm" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="col-md-5">
          <label for="new_keyword_token" class="form-label small fw-semibold mb-1">Keyword Token</label>
          <input type="text" class="form-control" name="keyword_token" id="new_keyword_token" placeholder="e.g. business" required>
        </div>
        <div class="col-md-3">
          <label for="new_language_code" class="form-label small fw-semibold mb-1">Language Code</label>
          <select class="form-select" name="language_code" id="new_language_code">
            <option value="en">English (en)</option>
            <option value="fr">French (fr)</option>
            <option value="ar">Arabic (ar)</option>
            <option value="ur">Urdu/Hindi (ur)</option>
          </select>
        </div>
        <div class="col-md-4 d-grid">
          <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center gap-1" id="submitNewSystemKeyword">
            <i class="bi bi-plus-lg"></i> Register Keyword
          </button>
        </div>
      </form>
    </div>

    <!-- Active Keywords Table -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Approved Token</th>
            <th>Language</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($keywords)): ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                <i class="bi bi-hash fs-1 d-block mb-2"></i> No spelling dictionary tokens registered yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($keywords as $k): ?>
              <tr>
                <td class="font-mono small text-secondary">#<?= $k['id'] ?></td>
                <td><strong class="text-dark font-mono"><?= htmlspecialchars($k['keyword_token']) ?></strong></td>
                <td><span class="badge bg-secondary-subtle text-secondary font-mono text-uppercase"><?= htmlspecialchars($k['language_code']) ?></span></td>
                <td class="small text-muted font-mono"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($k['created_at']))) ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 btn-delete-keyword" data-id="<?= $k['id'] ?>">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Hidden Delete Form -->
    <form method="post" action="bot_keywords.php" id="deleteKeywordForm">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id" id="deleteKeywordId">
    </form>

    <div class="d-flex justify-content-start border-top pt-3 mt-4">
      <a href="../dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Return to Dashboard</a>
    </div>
  </div>
</div>

</main>

<script nonce="<?php echo $cspNonce; ?>">
(function() {
    // Standard event bindings using `$cspNonce`
    const saveKeywordBtn = document.getElementById("submitNewSystemKeyword");
    const addKeywordForm = document.getElementById("addKeywordForm");

    function handleKeywordSubmitEvent(e) {
        e.preventDefault();
        const inputVal = document.getElementById("new_keyword_token").value.trim();
        if (inputVal === "") {
            alert("Spelling dictionary token cannot be empty!");
            return;
        }
        addKeywordForm.submit();
    }

    if (saveKeywordBtn) {
        saveKeywordBtn.onclick = handleKeywordSubmitEvent;
    }

    // Programmatically bind delete buttons to prevent inline JavaScript
    const deleteButtons = document.querySelectorAll(".btn-delete-keyword");
    const deleteForm = document.getElementById("deleteKeywordForm");
    const deleteIdInput = document.getElementById("deleteKeywordId");

    deleteButtons.forEach(function(btn) {
        btn.addEventListener("click", function() {
            const id = btn.getAttribute("data-id");
            if (confirm("Are you sure you want to permanently delete this spelling keyword token?")) {
                deleteIdInput.value = id;
                deleteForm.submit();
            }
        });
    });
})();
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
