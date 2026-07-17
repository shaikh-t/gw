<?php
// admin/settings/bot_analytics.php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/permissions.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

// Strict Super Admin Access Lock
if (!is_role('Super Admin')) {
    http_response_code(403);
    die("Access denied. Super Admin role required to view bot analytics.");
}

// 1. Language Preferences Aggregation
$languages_summary = [];
$res_lang = $mysqli->query("
    SELECT COALESCE(selected_language, 'en') AS lang, COUNT(id) AS session_count
    FROM bot_sessions
    GROUP BY lang
    ORDER BY session_count DESC
");
if ($res_lang) {
    while ($row = $res_lang->fetch_assoc()) {
        $languages_summary[] = $row;
    }
    $res_lang->free();
}

// 2. Query Active Chat Sessions for Audit Log
$active_sessions = [];
$res_sess = $mysqli->query("
    SELECT s.*, u.name as customer_name, u.email as customer_email,
           (SELECT COUNT(*) FROM bot_chat_logs WHERE session_id = s.id) AS msg_count
    FROM bot_sessions s
    LEFT JOIN users u ON u.id = s.user_id
    ORDER BY s.updated_at DESC, s.created_at DESC
    LIMIT 20
");
if ($res_sess) {
    while ($row = $res_sess->fetch_assoc()) {
        $active_sessions[] = $row;
    }
    $res_sess->free();
}

// 3. Query Failed Questions for Failure Monitor
$failed_questions = [];
$res_fail = $mysqli->query("
    SELECT f.*, u.name as customer_name, s.session_token
    FROM bot_failed_questions f
    LEFT JOIN users u ON u.id = f.user_id
    JOIN bot_sessions s ON s.id = f.session_id
    ORDER BY f.created_at DESC
    LIMIT 20
");
if ($res_fail) {
    while ($row = $res_fail->fetch_assoc()) {
        $failed_questions[] = $row;
    }
    $res_fail->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container-fluid mt-4 mb-5">
  <div class="mb-4">
    <a href="../dashboard.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
  </div>

  <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4">
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center text-danger" style="width: 48px; height: 48px;">
        <i class="bi bi-cpu-fill fs-4"></i>
      </div>
      <div>
        <h1 class="h4 mb-0 font-serif fw-bold">Super Admin Advanced Bot Analytics Portal</h1>
        <p class="text-muted small mb-0">Unified dashboard auditing real-time bot entry points, failure matrices, and full conversational transcripts.</p>
      </div>
    </div>

    <hr class="my-3">

    <div class="row g-4">
      <!-- LEFT PANEL: Traffic Metrics Visualizer -->
      <div class="col-xl-5 col-lg-6">
        <div class="card border-0 bg-light p-3 rounded-4 shadow-xs mb-4">
          <h5 class="h6 fw-bold text-dark mb-3"><i class="bi bi-pie-chart text-primary me-1"></i> Bot Entry Channels (Session Volume)</h5>
          <div style="max-height: 250px;" class="d-flex align-items-center justify-content-center">
            <canvas id="entryPointsPieChart" style="max-height: 220px;"></canvas>
          </div>
        </div>

        <div class="card border-0 bg-light p-3 rounded-4 shadow-xs">
          <h5 class="h6 fw-bold text-dark mb-3"><i class="bi bi-translate text-success me-1"></i> Localized Language Preferences</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Language Code</th>
                  <th class="text-center">Total Sessions</th>
                  <th>Visual Share</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($languages_summary)): ?>
                  <tr>
                    <td colspan="3" class="text-center py-3 text-muted">No language data recorded.</td>
                  </tr>
                <?php else: ?>
                  <?php
                    $total_l_sessions = array_sum(array_column($languages_summary, 'session_count')) ?: 1;
                    foreach ($languages_summary as $l):
                      $share = round(($l['session_count'] / $total_l_sessions) * 100, 1);
                  ?>
                    <tr>
                      <td class="font-mono fw-bold text-dark text-uppercase"><?= htmlspecialchars($l['lang']) ?></td>
                      <td class="text-center fw-semibold text-primary"><?= htmlspecialchars($l['session_count']) ?></td>
                      <td>
                        <div class="progress" style="height: 8px;" title="<?= $share ?>% share">
                          <div class="progress-bar bg-success" role="progressbar" style="width: <?= $share ?>%;" aria-valuenow="<?= $share ?>" aria-valuemin="0" aria-valuemax="100"></div>
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

      <!-- RIGHT PANEL: Live Chat Audit & Failure Monitor -->
      <div class="col-xl-7 col-lg-6">
        <!-- Section 1: Active Chat Audit Log -->
        <div class="card border-0 shadow-sm p-3 rounded-4 mb-4 bg-white border">
          <h5 class="h6 fw-bold text-dark mb-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-chat-left-heart-fill text-info me-1"></i> Live Chat Audit Logs</span>
            <span class="badge bg-info-subtle text-info font-mono small">Real-time sessions</span>
          </h5>
          <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light sticky-top">
                <tr>
                  <th>Customer Profile</th>
                  <th>Entry Point</th>
                  <th class="text-center">Msgs</th>
                  <th class="text-end">Transcript</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($active_sessions)): ?>
                  <tr>
                    <td colspan="4" class="text-center py-4 text-muted">No active sessions.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($active_sessions as $s): ?>
                    <tr>
                      <td>
                        <span class="fw-semibold text-dark text-truncate d-block" style="max-width: 180px;"><?= htmlspecialchars($s['customer_name'] ?: 'Guest Customer') ?></span>
                        <span class="small text-muted font-mono" style="font-size: 0.7rem;"><?= htmlspecialchars(substr($s['session_token'], 0, 16)) ?>...</span>
                      </td>
                      <td><span class="badge bg-light text-dark border font-mono small"><?= htmlspecialchars($s['entry_point'] ?: 'general') ?></span></td>
                      <td class="text-center fw-bold text-secondary"><?= $s['msg_count'] ?></td>
                      <td class="text-end">
                        <button class="btn btn-xs btn-outline-info rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="viewTranscript(<?= $s['id'] ?>, '<?= htmlspecialchars($s['customer_name'] ?: 'Guest') ?>')">
                          <i class="bi bi-clock-history"></i> Audit
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Section 2: Failure Monitor -->
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white border">
          <h5 class="h6 fw-bold text-danger mb-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-exclamation-octagon-fill me-1"></i> Failure Monitor (Unmapped Questions)</span>
            <span class="badge bg-danger-subtle text-danger font-mono small">Gaps logged</span>
          </h5>
          <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light sticky-top">
                <tr>
                  <th>Failed Query</th>
                  <th>Page URL</th>
                  <th class="text-end">Transcript</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($failed_questions)): ?>
                  <tr>
                    <td colspan="3" class="text-center py-4 text-muted">No failures recorded. All unmapped queries resolved.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($failed_questions as $q): ?>
                    <tr>
                      <td>
                        <div class="text-truncate text-danger small fw-semibold" style="max-width: 250px;" title="<?= htmlspecialchars($q['unanswered_question']) ?>">
                          <?= htmlspecialchars($q['unanswered_question']) ?>
                        </div>
                        <span class="small text-muted font-mono" style="font-size: 0.7rem;"><?= htmlspecialchars(date('M d h:i A', strtotime($q['created_at']))) ?></span>
                      </td>
                      <td>
                        <span class="small text-muted font-mono text-truncate d-block" style="max-width: 150px;" title="<?= htmlspecialchars($q['page_context_url']) ?>"><?= htmlspecialchars(basename($q['page_context_url'] ?: 'bot-landing.php')) ?></span>
                      </td>
                      <td class="text-end">
                        <button class="btn btn-xs btn-outline-danger rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="viewTranscript(<?= $q['session_id'] ?>, '<?= htmlspecialchars($q['customer_name'] ?: 'Guest') ?>')">
                          <i class="bi bi-journal-text"></i> Trace
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Transcript Audit Modal -->
<div class="modal fade" id="transcriptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="transcriptModalTitle">Audit Transcript</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light" style="max-height: 450px; overflow-y: auto;">
        <div id="transcriptView" class="d-flex flex-column gap-3 p-2">
          <!-- Transcripts are injected asynchronously here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close Audit</button>
        <a href="../crm/knowledge-base.php" class="btn btn-primary">Go to Knowledge Base</a>
      </div>
    </div>
  </div>
</div>

<!-- Embed Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Fetch Entry Point session stats asynchronously from graph API
    fetch('../../api/entry-point-charts.php')
        .then(response => response.json())
        .then(payload => {
            if (payload.status === 'success') {
                const ctx = document.getElementById('entryPointsPieChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: payload.labels,
                        datasets: payload.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 9 }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(err => console.error('Failed to load entry point chart metrics:', err));
});

// 2. Fetch and render chronological chat transcripts dynamically in modal
function viewTranscript(sessionId, customerName) {
    const titleEl = document.getElementById('transcriptModalTitle');
    const viewEl = document.getElementById('transcriptView');

    titleEl.innerText = `Audit Chat Session ID #${sessionId} (Customer: ${customerName})`;
    viewEl.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div> Loading transcript logs...</div>';

    // Open Modal
    const modal = new bootstrap.Modal(document.getElementById('transcriptModal'));
    modal.show();

    // Fetch chronological session lines asynchronously
    fetch(`../../api/bot-transcript.php?session_id=${sessionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.transcript.length > 0) {
                viewEl.innerHTML = '';
                data.transcript.forEach(line => {
                    const bubble = document.createElement('div');
                    const isUser = (line.sender === 'user');

                    bubble.className = `p-3 rounded-3 max-width-75 shadow-xs ${isUser ? 'bg-primary text-white align-self-end' : 'bg-white text-dark align-self-start border'}`;
                    bubble.style.maxWidth = '75%';
                    bubble.innerHTML = `
                        <div class="fw-bold mb-1" style="font-size: 0.7rem; opacity: 0.8;">
                            ${isUser ? 'CUSTOMER' : 'CONCIERGE BOT'} · <span class="font-mono font-normal">${line.created_at}</span>
                        </div>
                        <div style="font-size: 0.85rem; line-height: 1.4;">${escapeHtml(line.message_content)}</div>
                    `;
                    viewEl.appendChild(bubble);
                });
            } else {
                viewEl.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-chat-slash fs-3 d-block mb-2"></i> No conversation logs logged for this session yet.</div>';
            }
        })
        .catch(err => {
            console.error('Failed to audit transcript:', err);
            viewEl.innerHTML = '<div class="alert alert-danger">Error loading transcript from API.</div>';
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
