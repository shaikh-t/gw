<?php
// admin/settings/entry-analytics.php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/permissions.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';

// Gate access strictly to administrative profiles
if (!is_role('Super Admin') && !is_role('admin') && !is_role('Manager')) {
    http_response_code(403);
    die("Access denied.");
}

// Fetch session log metrics grouped dynamically by entry_point
$entry_points_summary = [];
$sql = "
    SELECT
        COALESCE(NULLIF(TRIM(entry_point), ''), 'general_widget') AS ep,
        COUNT(id) AS session_count,
        MIN(created_at) AS first_active,
        MAX(created_at) AS last_active
    FROM bot_sessions
    GROUP BY ep
    ORDER BY session_count DESC
";
$res = $mysqli->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $entry_points_summary[] = $row;
    }
    $res->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4 mb-5">
  <div class="mb-4">
    <a href="../dashboard.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
  </div>

  <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4">
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
        <i class="bi bi-bar-chart-line-fill fs-4"></i>
      </div>
      <div>
        <h1 class="h4 mb-0 font-serif fw-bold">Bot Entry Point Analytics</h1>
        <p class="text-muted small mb-0">Track customer interaction channels, active voice bot entry points, and dialogue traffic log charts.</p>
      </div>
    </div>

    <hr class="my-3">

    <div class="row g-4">
      <!-- Left Column: Interactive Chart.js Doughnut Graph -->
      <div class="col-lg-5 text-center">
        <div class="card border p-3 rounded-4 bg-light shadow-xs mb-3">
          <h5 class="h6 fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill text-primary"></i> Traffic Source Distribution</h5>
          <div style="max-width: 320px; margin: 0 auto; min-height: 250px;" class="d-flex align-items-center justify-content-center">
            <canvas id="entryPointChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Right Column: Traffic Summary Table -->
      <div class="col-lg-7">
        <div class="card border p-3 rounded-4 bg-white shadow-xs">
          <h5 class="h6 fw-bold text-dark mb-3"><i class="bi bi-list-task text-secondary"></i> Interactive Session Traffic Summary</h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Entry Channel</th>
                  <th class="text-center">Active Sessions</th>
                  <th>First Activity</th>
                  <th>Last Active</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($entry_points_summary)): ?>
                  <tr>
                    <td colspan="4" class="text-center py-4 text-muted">No bot traffic recorded yet.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($entry_points_summary as $summary): ?>
                    <tr>
                      <td class="fw-bold text-dark font-mono text-uppercase" style="font-size: 0.8rem;">
                        <i class="bi bi-circle-fill me-1 text-primary" style="font-size: 0.5rem;"></i>
                        <?= htmlspecialchars(str_replace('_', ' ', $summary['ep'])) ?>
                      </td>
                      <td class="text-center fw-semibold text-primary"><?= htmlspecialchars($summary['session_count']) ?></td>
                      <td class="small text-muted font-mono"><?= htmlspecialchars(date('M d, Y', strtotime($summary['first_active']))) ?></td>
                      <td class="small text-muted font-mono"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($summary['last_active']))) ?></td>
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

<!-- Embed Chart.js library directly -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch Chart JSON payload asynchronously from our secure dashboard graph API
    fetch('../../api/entry-point-charts.php')
        .then(response => response.json())
        .then(payload => {
            if (payload.status === 'success') {
                const ctx = document.getElementById('entryPointChart').getContext('2d');
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
                                    boxWidth: 12,
                                    font: { size: 10 }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(err => console.error('Failed to load entry point charts:', err));
});
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
