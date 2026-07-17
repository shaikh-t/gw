<?php
// admin/crm/failed-questions.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.view');

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

    <div class="alert alert-light border d-flex align-items-center gap-3 mb-4 rounded-3 p-3">
      <div class="fs-4 text-warning"><i class="bi bi-lightbulb"></i></div>
      <div class="small text-secondary">
        <strong>Tip:</strong> Review unmapped queries below to identify knowledge gaps in your guidelines. Click the button above to upload PDF documentation or policies to automatically resolve these questions in real-time.
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
          </tr>
        </thead>
        <tbody>
          <?php if (empty($failed_questions)): ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
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
                  <div class="p-2 bg-light rounded text-dark small border-start border-danger border-3" style="max-width: 450px; word-wrap: break-word; white-space: pre-wrap;">
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

</main>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
