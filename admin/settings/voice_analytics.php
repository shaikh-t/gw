<?php
// admin/settings/voice_analytics.php
require_once __DIR__ . '/../../lib/middleware.php';

// Strict permission check dropping execution stream with 403 Forbidden payload immediately
if (!can('manage_system_analytics') || !can('view_voice_telemetry')) {
    http_response_code(403);
    header('HTTP/1.1 403 Forbidden');
    echo "<h1>403 Forbidden</h1><p>Access Denied. Insufficient Permissions.</p>";
    exit;
}

require_once __DIR__ . '/../../lib/db_mysqli.php';

$success_message = '';
$error_message = '';

// Handle configuration saves
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF token');
    }

    $settings = [
        'google_analytics_status' => (isset($_POST['google_analytics_status']) && $_POST['google_analytics_status'] === 'ON') ? 'ON' : 'OFF',
        'google_analytics_measurement_id' => trim($_POST['google_analytics_measurement_id'] ?? ''),
        'elevenlabs_status' => (isset($_POST['elevenlabs_status']) && $_POST['elevenlabs_status'] === 'ON') ? 'ON' : 'OFF',
        'elevenlabs_api_key' => trim($_POST['elevenlabs_api_key'] ?? ''),
        'elevenlabs_voice_id' => trim($_POST['elevenlabs_voice_id'] ?? ''),
        'elevenlabs_stability' => trim($_POST['elevenlabs_stability'] ?? '0.75'),
        'elevenlabs_clarity' => trim($_POST['elevenlabs_clarity'] ?? '0.75')
    ];

    $all_ok = true;
    foreach ($settings as $key => $val) {
        $stmt_up = $mysqli->prepare("UPDATE `site_settings` SET `value` = ? WHERE `key` = ?");
        if ($stmt_up) {
            $stmt_up->bind_param('ss', $val, $key);
            if (!$stmt_up->execute()) {
                $all_ok = false;
            }
            $stmt_up->close();
        } else {
            $all_ok = false;
        }

        // Bypass / clear application-layer caching layer live
        if (function_exists('cache_delete')) {
            cache_delete('setting_' . $key);
        }
    }

    if (function_exists('cache_delete')) {
        cache_delete('all_site_settings');
    }

    if ($all_ok) {
        $success_message = "Voice and Analytics Configuration saved successfully.";
    } else {
        $error_message = "An error occurred while saving the configuration parameters.";
    }
}

// Fetch live configuration variables directly from physical layer bypassing caching
$settings_vals = [
    'google_analytics_status' => 'OFF',
    'google_analytics_measurement_id' => 'UA-XXXXX-Y',
    'elevenlabs_status' => 'OFF',
    'elevenlabs_api_key' => '',
    'elevenlabs_voice_id' => '21m00Tcm4TlvDq8ikWAM',
    'elevenlabs_stability' => '0.75',
    'elevenlabs_clarity' => '0.75'
];

$res_sets = $mysqli->query("SELECT `key`, `value` FROM `site_settings` WHERE `key` IN ('google_analytics_status', 'google_analytics_measurement_id', 'elevenlabs_status', 'elevenlabs_api_key', 'elevenlabs_voice_id', 'elevenlabs_stability', 'elevenlabs_clarity')");
if ($res_sets) {
    while ($row = $res_sets->fetch_assoc()) {
        $settings_vals[$row['key']] = $row['value'];
    }
}

// Fetch voice telemetry metrics for visualization
$telemetry_logs = [];
$res_tel = $mysqli->query("SELECT * FROM `voice_telemetry_logs` ORDER BY id DESC LIMIT 20");
if ($res_tel) {
    while ($row = $res_tel->fetch_assoc()) {
        $telemetry_logs[] = $row;
    }
}

// Aggregate totals for overview cards & charts
$engine_counts = ['elevenlabs' => 0, 'native' => 0];
$total_chars = 0;
$total_errors = 0;
$res_agg = $mysqli->query("SELECT engine, COUNT(*) as cnt, SUM(characters_used) as total_chars, SUM(is_error) as total_errs FROM `voice_telemetry_logs` GROUP BY engine");
if ($res_agg) {
    while ($row = $res_agg->fetch_assoc()) {
        $eng = $row['engine'];
        if (isset($engine_counts[$eng])) {
            $engine_counts[$eng] = (int)$row['cnt'];
        }
        $total_chars += (int)$row['total_chars'];
        $total_errors += (int)$row['total_errs'];
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container-fluid mt-4 mb-5">
  <div class="mb-4">
    <a href="../dashboard.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
  </div>

  <!-- Telemetry Metrics KPI Grid -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3 bg-white rounded-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
            <i class="bi bi-cpu fs-4"></i>
          </div>
          <div>
            <h6 class="text-muted small mb-1">ElevenLabs Queries</h6>
            <h3 class="fw-bold mb-0 font-mono"><?= $engine_counts['elevenlabs'] ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3 bg-white rounded-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-secondary" style="width: 48px; height: 48px;">
            <i class="bi bi-arrow-down-left-circle fs-4"></i>
          </div>
          <div>
            <h6 class="text-muted small mb-1">Native Fallback Calls</h6>
            <h3 class="fw-bold mb-0 font-mono"><?= $engine_counts['native'] ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3 bg-white rounded-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center text-success" style="width: 48px; height: 48px;">
            <i class="bi bi-fonts fs-4"></i>
          </div>
          <div>
            <h6 class="text-muted small mb-1">Total Chars Consumed</h6>
            <h3 class="fw-bold mb-0 font-mono"><?= number_format($total_chars) ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3 bg-white rounded-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center text-danger" style="width: 48px; height: 48px;">
            <i class="bi bi-exclamation-octagon fs-4"></i>
          </div>
          <div>
            <h6 class="text-muted small mb-1">Total TTS Errors</h6>
            <h3 class="fw-bold mb-0 font-mono text-danger"><?= $total_errors ?></h3>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Settings Configuration Form -->
    <div class="col-lg-6">
      <?php if ($success_message !== ''): ?>
        <div class="alert alert-success shadow-sm rounded-3 mb-4"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>

      <?php if ($error_message !== ''): ?>
        <div class="alert alert-danger shadow-sm rounded-3 mb-4"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center text-info" style="width: 48px; height: 48px;">
            <i class="bi bi-sliders fs-4"></i>
          </div>
          <div>
            <h2 class="h5 mb-0 fw-bold">Voice & Analytics Control Panel</h2>
            <p class="text-muted small mb-0">Configure premium text-to-speech settings and tracker variables.</p>
          </div>
        </div>

        <hr class="my-3">

        <form action="voice_analytics.php" method="post" id="voiceAnalyticsForm">
          <?= csrf_field(); ?>

          <!-- Section A: Google Analytics -->
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-google text-primary me-2"></i>Google Analytics Tag</h5>
          <div class="mb-3">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" role="switch" name="google_analytics_status" value="ON" id="gaStatusToggle" <?= $settings_vals['google_analytics_status'] === 'ON' ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold text-dark" for="gaStatusToggle">Enable Google Analytics Integration</label>
            </div>
            <p class="text-muted small">Toggles standard Google Tag tracking code dynamically inside the headers.</p>
          </div>

          <div class="mb-4" id="gaIdWrapper">
            <label class="form-label small fw-bold text-secondary" for="gaMeasurementId">Measurement ID (G-XXXXXXX)</label>
            <input type="text" class="form-control" name="google_analytics_measurement_id" id="gaMeasurementId" value="<?= htmlspecialchars($settings_vals['google_analytics_measurement_id'], ENT_QUOTES, 'UTF-8') ?>" placeholder="G-7AXXXXXX">
          </div>

          <hr class="my-4">

          <!-- Section B: ElevenLabs TTS -->
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-soundwave text-success me-2"></i>ElevenLabs Premium TTS Engine</h5>
          <div class="mb-3">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" role="switch" name="elevenlabs_status" value="ON" id="elStatusToggle" <?= $settings_vals['elevenlabs_status'] === 'ON' ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold text-dark" for="elStatusToggle">Enable ElevenLabs Premium Engine</label>
            </div>
            <p class="text-muted small mb-0">If disabled, the application forces immediate browser-native speech synthesis fallback.</p>
          </div>

          <div id="elFieldsWrapper">
            <div class="mb-3">
              <label class="form-label small fw-bold text-secondary" for="elApiKey">API Secret Key</label>
              <input type="password" class="form-control" name="elevenlabs_api_key" id="elApiKey" value="<?= htmlspecialchars($settings_vals['elevenlabs_api_key'], ENT_QUOTES, 'UTF-8') ?>" placeholder="••••••••••••••••••••••••">
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-secondary" for="elVoiceId">Voice ID Mapping Variable</label>
              <input type="text" class="form-control" name="elevenlabs_voice_id" id="elVoiceId" value="<?= htmlspecialchars($settings_vals['elevenlabs_voice_id'], ENT_QUOTES, 'UTF-8') ?>" placeholder="21m00Tcm4TlvDq8ikWAM">
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <label class="form-label small fw-bold text-secondary" for="elStability">Stability Slider Range</label>
                <span class="small font-mono text-success" id="elStabilityValue"><?= $settings_vals['elevenlabs_stability'] ?></span>
              </div>
              <input type="range" class="form-range" min="0.0" max="1.0" step="0.05" name="elevenlabs_stability" id="elStability" value="<?= htmlspecialchars($settings_vals['elevenlabs_stability'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between">
                <label class="form-label small fw-bold text-secondary" for="elClarity">Clarity / Similarity Slider Range</label>
                <span class="small font-mono text-success" id="elClarityValue"><?= $settings_vals['elevenlabs_clarity'] ?></span>
              </div>
              <input type="range" class="form-range" min="0.0" max="1.0" step="0.05" name="elevenlabs_clarity" id="elClarity" value="<?= htmlspecialchars($settings_vals['elevenlabs_clarity'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm" id="btnSubmitSettings">
            <i class="bi bi-save2-fill me-1"></i> Save Configuration variables
          </button>
        </form>
      </div>
    </div>

    <!-- Right Column: Dashboard Telemetry Charts -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4">
        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-bar-chart-line text-info me-2"></i>Telemetry Visualizations</h5>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light text-center h-100">
              <h6 class="small fw-bold text-secondary mb-3">Usage Distribution (Engine)</h6>
              <div style="height: 160px;" class="d-flex align-items-center justify-content-center">
                <canvas id="engineDistChart" style="max-height: 150px;"></canvas>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light text-center h-100">
              <h6 class="small fw-bold text-secondary mb-3">Char Consumption vs Errors</h6>
              <div style="height: 160px;" class="d-flex align-items-center justify-content-center">
                <canvas id="charsErrChart" style="max-height: 150px;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="border rounded-3 p-3 bg-light text-center">
          <h6 class="small fw-bold text-secondary mb-3">Real-time Server Processing Load Management</h6>
          <div style="height: 180px;">
            <canvas id="serverLoadChart" style="max-height: 170px;"></canvas>
          </div>
        </div>
      </div>

      <!-- Live Logs Trace Monitor -->
      <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-journal-text text-danger me-2"></i>Live Telemetry Trace Monitor</h5>
        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Time</th>
                <th>Engine</th>
                <th>Chars</th>
                <th>Status</th>
                <th>Load</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($telemetry_logs)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No telemetry trace logged yet. Speak to the bot or use TTS.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($telemetry_logs as $log): ?>
                  <tr>
                    <td class="font-mono small text-secondary"><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                    <td>
                      <span class="badge <?= $log['engine'] === 'elevenlabs' ? 'bg-primary' : 'bg-secondary' ?> text-uppercase small">
                        <?= htmlspecialchars($log['engine']) ?>
                      </span>
                    </td>
                    <td class="fw-semibold text-dark"><?= $log['characters_used'] ?></td>
                    <td>
                      <?php if ($log['is_error'] == 1): ?>
                        <span class="badge bg-danger" title="<?= htmlspecialchars($log['error_message']) ?>">ERROR</span>
                      <?php else: ?>
                        <span class="badge bg-success">SUCCESS</span>
                      <?php endif; ?>
                    </td>
                    <td class="font-mono text-secondary"><?= $log['server_load'] ?>s</td>
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

<!-- Embed Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="<?php echo $cspNonce; ?>">
  // Safe JSON extraction for chart data feeding
  const engineCounts = <?= json_encode($engine_counts) ?>;
  const recentLogs = <?= json_encode(array_reverse($telemetry_logs)) ?>;

  document.addEventListener('DOMContentLoaded', () => {
    // 1. Programmatic Event Binding for Form Interactivity (CSP Compliant)
    const gaStatusToggle = document.getElementById('gaStatusToggle');
    const gaIdWrapper = document.getElementById('gaIdWrapper');
    const elStatusToggle = document.getElementById('elStatusToggle');
    const elFieldsWrapper = document.getElementById('elFieldsWrapper');
    const elStability = document.getElementById('elStability');
    const elStabilityValue = document.getElementById('elStabilityValue');
    const elClarity = document.getElementById('elClarity');
    const elClarityValue = document.getElementById('elClarityValue');

    const updateGAFormVisibility = () => {
      if (gaStatusToggle && gaIdWrapper) {
        gaIdWrapper.style.display = gaStatusToggle.checked ? 'block' : 'none';
      }
    };

    const updateELFormVisibility = () => {
      if (elStatusToggle && elFieldsWrapper) {
        elFieldsWrapper.style.display = elStatusToggle.checked ? 'block' : 'none';
      }
    };

    if (gaStatusToggle) {
      gaStatusToggle.addEventListener('change', updateGAFormVisibility);
      updateGAFormVisibility();
    }

    if (elStatusToggle) {
      elStatusToggle.addEventListener('change', updateELFormVisibility);
      updateELFormVisibility();
    }

    if (elStability && elStabilityValue) {
      elStability.addEventListener('input', (e) => {
        elStabilityValue.textContent = e.target.value;
      });
    }

    if (elClarity && elClarityValue) {
      elClarity.addEventListener('input', (e) => {
        elClarityValue.textContent = e.target.value;
      });
    }

    // 2. Telemetry Charts Rendering
    // Chart A: Engine distribution doughnut
    const ctxA = document.getElementById('engineDistChart');
    if (ctxA) {
      new Chart(ctxA, {
        type: 'doughnut',
        data: {
          labels: ['ElevenLabs', 'Native Fallback'],
          datasets: [{
            data: [engineCounts.elevenlabs, engineCounts.native],
            backgroundColor: ['#1165ef', '#6b7280'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } }
          }
        }
      });
    }

    // Chart B: Characters consumed vs errors trends
    const ctxB = document.getElementById('charsErrChart');
    if (ctxB) {
      // Calculate from recentLogs
      const labels = recentLogs.map((_, i) => `#${i + 1}`);
      const chars = recentLogs.map(log => log.characters_used);
      const errs = recentLogs.map(log => log.is_error);

      new Chart(ctxB, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Characters',
              data: chars,
              backgroundColor: '#10b981',
              yAxisID: 'y'
            },
            {
              label: 'Errors',
              data: errs,
              backgroundColor: '#ef4444',
              yAxisID: 'y1',
              type: 'line',
              borderColor: '#ef4444',
              borderWidth: 2,
              fill: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } }
          },
          scales: {
            y: { type: 'linear', position: 'left' },
            y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
          }
        }
      });
    }

    // Chart C: Server Load Trends line chart
    const ctxC = document.getElementById('serverLoadChart');
    if (ctxC) {
      const labels = recentLogs.map((_, i) => `#${i + 1}`);
      const load = recentLogs.map(log => parseFloat(log.server_load || 0));

      new Chart(ctxC, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Response Time / Load (s)',
            data: load,
            borderColor: '#38bdf8',
            backgroundColor: 'rgba(56, 189, 248, 0.1)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    }
  });
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
