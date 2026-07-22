<?php
// admin/analytics/bot_logs.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('view_bot_interaction_logs');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

if (!isset($cspNonce)) {
    $cspNonce = base64_encode(random_bytes(16));
}

// Fetch filter inputs
$filter_session_id = trim($_GET['session_id'] ?? '');
$filter_user_id = trim($_GET['user_id'] ?? '');
$filter_match_type = trim($_GET['match_type'] ?? '');

// 1. Fetch Fallout Funnel Aggregates
$funnel_counts = [];
$res_funnel = $mysqli->query("SELECT active_state_token, COUNT(*) as cnt FROM bot_interaction_logs GROUP BY active_state_token ORDER BY cnt DESC");
if ($res_funnel) {
    while ($row = $res_funnel->fetch_assoc()) {
        $funnel_counts[$row['active_state_token']] = (int)$row['cnt'];
    }
}

// 2. Fetch Filtered Interactive Logs
$query = "SELECT l.*, u.name as user_name FROM bot_interaction_logs l LEFT JOIN users u ON u.id = l.user_id WHERE 1=1";
$params = [];
$types = '';

if ($filter_session_id !== '') {
    $query .= " AND l.session_id LIKE ?";
    $like_session = '%' . $filter_session_id . '%';
    $params[] = $like_session;
    $types .= 's';
}

if ($filter_user_id !== '') {
    $query .= " AND l.user_id = ?";
    $params[] = (int)$filter_user_id;
    $types .= 'i';
}

if ($filter_match_type !== '') {
    $query .= " AND l.match_type = ?";
    $params[] = $filter_match_type;
    $types .= 's';
}

$query .= " ORDER BY l.created_at DESC LIMIT 100";

$stmt = $mysqli->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res_logs = $stmt->get_result();
    $logs = [];
    if ($res_logs) {
        while ($row = $res_logs->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt->close();
} else {
    $logs = [];
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4">
  <div class="mb-4">
    <h1 class="h3 fw-bold text-dark font-serif mb-1">Conversational Telemetry & Interactions</h1>
    <p class="text-secondary small mb-0">Monitor chronological multi-lingual paths, track fallout funnels, and analyze conversational queries live.</p>
  </div>

  <!-- Fallout Funnel Aggregate Indicator Widget -->
  <div class="card border-0 shadow-sm p-4 mb-4 bg-white rounded-4">
    <h2 class="h5 fw-bold text-dark mb-3"><i class="bi bi-funnel-fill text-primary me-1"></i> Funnel Fallout Patterns</h2>
    <p class="text-muted small mb-4">Total aggregate interaction breakdown count per active step token. Fallout indicates where users abandon workflow sequences.</p>

    <div class="row g-3">
      <?php if (empty($funnel_counts)): ?>
        <div class="col-12 text-center text-muted">No interaction logs captured yet.</div>
      <?php else: ?>
        <?php foreach ($funnel_counts as $token => $cnt): ?>
          <div class="col-md-3">
            <div class="p-3 border rounded-3 bg-light text-center">
              <span class="text-secondary font-mono small d-block mb-1"><?= htmlspecialchars($token ?: 'Unknown') ?></span>
              <strong class="fs-3 text-dark"><?= $cnt ?></strong>
              <span class="small d-block text-muted">Interactions</span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filters Area -->
  <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-4">
    <form method="get" action="bot_logs.php" id="logsFilterForm" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="session_id" class="form-label small fw-bold">Session ID</label>
        <input type="text" class="form-control" name="session_id" id="session_id" value="<?= htmlspecialchars($filter_session_id) ?>" placeholder="Filter by Session ID">
      </div>
      <div class="col-md-3">
        <label for="user_id" class="form-label small fw-bold">User ID</label>
        <input type="number" class="form-control" name="user_id" id="user_id" value="<?= htmlspecialchars($filter_user_id) ?>" placeholder="Filter by User ID">
      </div>
      <div class="col-md-3">
        <label for="match_type" class="form-label small fw-bold">Match Type</label>
        <select class="form-select" name="match_type" id="match_type">
          <option value="">-- All Match Types --</option>
          <option value="workflow_step" <?= $filter_match_type === 'workflow_step' ? 'selected' : '' ?>>Workflow Step</option>
          <option value="rag_fallback" <?= $filter_match_type === 'rag_fallback' ? 'selected' : '' ?>>RAG Fallback / Unmapped</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary" id="btnFilterLogs">
          <i class="bi bi-filter"></i> Apply Filters
        </button>
      </div>
    </form>
  </div>

  <!-- Interactive Listing Logs -->
  <div class="card border-0 shadow-sm p-3 bg-white rounded-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="h6 fw-bold text-dark mb-0">Chronological Lifecycle Logs</h3>
      <span class="badge bg-secondary font-mono">Showing Last 100 Entries</span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Timestamp</th>
            <th>Session / User</th>
            <th>Match Type</th>
            <th>State Token</th>
            <th>Transcript / Prompt</th>
            <th>Bot Response</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No conversational telemetry logs matched your query.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td class="small font-mono text-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                <td>
                  <div class="small font-mono text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($log['session_id']) ?>">
                    <strong>Session:</strong> <?= htmlspecialchars($log['session_id']) ?>
                  </div>
                  <?php if (!empty($log['user_id'])): ?>
                    <div class="small text-secondary">
                      <strong>User:</strong> <?= htmlspecialchars($log['user_name'] ?? 'ID ' . $log['user_id']) ?>
                    </div>
                  <?php else: ?>
                    <span class="badge bg-light text-secondary font-mono">Guest</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $log['match_type'] === 'workflow_step' ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= htmlspecialchars($log['match_type']) ?>
                  </span>
                </td>
                <td><span class="badge bg-dark font-mono"><?= htmlspecialchars($log['active_state_token']) ?></span></td>
                <td style="max-width: 250px;">
                  <div class="small text-dark text-wrap"><?= htmlspecialchars($log['spoken_text_transcript'] ?: '—') ?></div>
                </td>
                <td style="max-width: 350px;">
                  <div class="small text-secondary text-wrap" style="max-height: 80px; overflow-y: auto;">
                    <?= htmlspecialchars($log['bot_response_text']) ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
(function() {
    const filterForm = document.getElementById("logsFilterForm");
    const filterBtn = document.getElementById("btnFilterLogs");
    if (filterForm && filterBtn) {
        filterBtn.onclick = function() {
            filterForm.submit();
        };
    }
})();
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
