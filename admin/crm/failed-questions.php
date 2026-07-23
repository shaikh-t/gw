<?php
// admin/crm/failed-questions.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('can_view_failed_queries');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

if (!isset($cspNonce)) {
    $cspNonce = base64_encode(random_bytes(16));
}

// Handle Form Submission for Mapping Synonyms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'map_synonym') {
    csrf_check($_POST['csrf_token'] ?? '');

    $failed_id = (int)($_POST['failed_id'] ?? 0);
    $system_intent_key = trim($_POST['system_intent_key'] ?? '');
    $phrase_variant = trim($_POST['phrase_variant'] ?? '');
    $language_code = trim($_POST['language_code'] ?? 'en');

    if ($system_intent_key !== '' && $phrase_variant !== '') {
        // Insert into bot_intent_synonyms
        $stmt_ins = $mysqli->prepare("INSERT INTO bot_intent_synonyms (system_intent_key, phrase_variant, language_code) VALUES (?, ?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('sss', $system_intent_key, $phrase_variant, $language_code);
            $stmt_ins->execute();
            $stmt_ins->close();
        }

        // Delete from bot_failed_questions
        $stmt_del = $mysqli->prepare("DELETE FROM bot_failed_questions WHERE id = ?");
        if ($stmt_del) {
            $stmt_del->bind_param('i', $failed_id);
            $stmt_del->execute();
            $stmt_del->close();
        }

        $_SESSION['flash_success'] = "Phrase variant successfully mapped to '{$system_intent_key}' and failed question resolved!";
    } else {
        $_SESSION['flash_error'] = "Missing required parameters for mapping.";
    }

    header("Location: failed-questions.php");
    exit;
}

// Fetch active step_keys from bot_workflow_steps for dropdown
$workflow_steps = [];
$res_steps = $mysqli->query("SELECT DISTINCT step_key FROM bot_workflow_steps ORDER BY step_key ASC");
if ($res_steps) {
    while ($row = $res_steps->fetch_assoc()) {
        $workflow_steps[] = $row['step_key'];
    }
    $res_steps->free();
}

// Fetch failed questions chronologically
$failed_questions = [];
$sql = "
    SELECT f.*, u.name AS customer_name, u.email AS customer_email, s.session_token, s.entry_point
    FROM bot_failed_questions f
    LEFT JOIN users u ON u.id = f.user_id
    JOIN bot_sessions s ON s.id = f.session_id
    ORDER BY f.created_at DESC
";
$res = $mysqli->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $failed_questions[] = $row;
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
        <h2 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-question-circle-fill text-danger"></i> Unmapped Failed Questions Audit</h2>
        <p class="text-muted small mb-0">Review questions that the AI assistant could not map or answer from our Local RAG database.</p>
      </div>
      <a href="../import-pdf.php" class="btn btn-primary d-flex align-items-center gap-1">
        <i class="bi bi-file-earmark-pdf-fill"></i> Upload Knowledge Base Asset
      </a>
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

    <div class="alert alert-light border d-flex align-items-center gap-3 mb-4 rounded-3 p-3">
      <div class="fs-4 text-warning"><i class="bi bi-lightbulb"></i></div>
      <div class="small text-secondary">
        <strong>Tip:</strong> Review unmapped queries below to identify knowledge gaps. Click <strong>Map as Alternative Phrase</strong> to cleanly map variations directly to standard workflow steps, letting the system learn phrasing instantly.
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Customer</th>
            <th>Language</th>
            <th>Failed Question</th>
            <th>Page Context / Entry Point</th>
            <th>Date Logged</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($failed_questions)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i> All quiet! No unmapped questions currently logged.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($failed_questions as $q): ?>
              <tr>
                <td class="fw-semibold text-dark">
                  <?php if ($q['customer_name']): ?>
                    <span class="d-block text-dark"><?= htmlspecialchars($q['customer_name']) ?></span>
                    <span class="small text-muted font-mono" style="font-size: 0.75rem;"><?= htmlspecialchars($q['customer_email']) ?></span>
                  <?php else: ?>
                    <span class="text-muted italic"><i class="bi bi-person-dash"></i> Guest Customer</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary text-uppercase"><?= htmlspecialchars($q['language_iso']) ?></span>
                </td>
                <td>
                  <div class="p-2 bg-light rounded text-dark small border-start border-danger border-3" style="max-width: 400px; word-wrap: break-word; white-space: pre-wrap;">
                    <?= htmlspecialchars($q['unanswered_question']) ?>
                  </div>
                </td>
                <td>
                  <div class="small text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($q['page_context_url']) ?>">
                    <span class="text-muted">URL:</span> <a href="<?= htmlspecialchars($q['page_context_url']) ?>" target="_blank" class="text-decoration-none font-mono"><?= htmlspecialchars($q['page_context_url']) ?></a>
                  </div>
                  <div class="small text-muted" style="font-size: 0.75rem;">
                    <span class="text-muted">Entry point:</span> <span class="badge bg-light text-dark font-mono border"><?= htmlspecialchars($q['entry_point'] ?: 'none') ?></span>
                  </div>
                </td>
                <td>
                  <span class="small text-muted font-mono"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($q['created_at']))) ?></span>
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary btn-map-synonym d-flex align-items-center gap-1"
                          data-id="<?= htmlspecialchars($q['id']) ?>"
                          data-question="<?= htmlspecialchars($q['unanswered_question']) ?>"
                          data-lang="<?= htmlspecialchars($q['language_iso']) ?>">
                    <i class="bi bi-diagram-3"></i> Map as Alternative Phrase
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-start border-top pt-3 mt-4">
      <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Return to CRM Customers</a>
    </div>
  </div>
</div>

<!-- Map Synonym Modal -->
<div class="modal fade" id="mapSynonymModal" tabindex="-1" aria-labelledby="mapSynonymModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="failed-questions.php" id="mapSynonymForm">
        <input type="hidden" name="action" value="map_synonym">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="failed_id" id="modalFailedId">

        <div class="modal-header">
          <h5 class="modal-title" id="mapSynonymModalLabel"><i class="bi bi-diagram-3-fill text-primary me-1"></i> Map Alternative Phrase</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="modalPhraseVariant" class="form-label fw-bold small mb-1">Phrase Variant</label>
            <input type="text" class="form-control" name="phrase_variant" id="modalPhraseVariant" readonly required>
            <div class="form-text small text-muted">The exact phrase variant that will trigger mapping.</div>
          </div>
          <div class="mb-3">
            <label for="modalLanguageCode" class="form-label fw-bold small mb-1">Language Code</label>
            <input type="text" class="form-control" name="language_code" id="modalLanguageCode" readonly required>
          </div>
          <div class="mb-3">
            <label for="modalSystemIntentKey" class="form-label fw-bold small mb-1">Select System Intent / Workflow Step Key</label>
            <select class="form-select" name="system_intent_key" id="modalSystemIntentKey" required>
              <option value="">-- Choose Target Step Key --</option>
              <?php foreach ($workflow_steps as $step): ?>
                <option value="<?= htmlspecialchars($step) ?>"><?= htmlspecialchars($step) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text small text-muted">Link this phrase directly to an existing active workflow step.</div>
          </div>
        </div>
        <div class="modal-footer border-top justify-content-end gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitSynonym">Save Mapping</button>
        </div>
      </form>
    </div>
  </div>
</div>

</main>

<script nonce="<?php echo $cspNonce; ?>">
(function() {
    document.addEventListener("DOMContentLoaded", function() {
        const buttons = document.querySelectorAll(".btn-map-synonym");
        const modal = new bootstrap.Modal(document.getElementById("mapSynonymModal"));

        buttons.forEach(function(btn) {
            btn.addEventListener("click", function() {
                const id = btn.getAttribute("data-id");
                const question = btn.getAttribute("data-question");
                const lang = btn.getAttribute("data-lang") || "en";

                document.getElementById("modalFailedId").value = id;
                document.getElementById("modalPhraseVariant").value = question;
                document.getElementById("modalLanguageCode").value = lang;

                modal.show();
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
