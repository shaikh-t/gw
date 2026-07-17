<?php
// admin/settings/bot_ads.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('can_manage_ads');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

$errors = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $campaign_name = trim($_POST['campaign_name'] ?? '');
        $ad_source_type = trim($_POST['ad_source_type'] ?? 'direct_sponsor');
        $placement_zone = trim($_POST['placement_zone'] ?? 'site_header_leaderboard');
        $target_page_context = trim($_POST['target_page_context'] ?? 'global_fallback');
        $target_category_id = !empty($_POST['target_category_id']) ? (int)$_POST['target_category_id'] : null;
        $language_iso = trim($_POST['language_iso'] ?? 'en');
        $banner_text = trim($_POST['banner_text'] ?? '');
        $audio_speech_text = trim($_POST['audio_speech_text'] ?? '');
        $destination_url = trim($_POST['destination_url'] ?? '');
        $network_script_code = trim($_POST['network_script_code'] ?? '');
        $click_cost = (float)($_POST['click_cost'] ?? 0.00);
        $max_budget = (float)($_POST['max_budget'] ?? 0.00);
        $ad_billing_model = trim($_POST['ad_billing_model'] ?? 'ppc');
        $max_impressions = (int)($_POST['max_impressions'] ?? 0);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($campaign_name === '') {
            $errors[] = "Campaign Name is required.";
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $mysqli->prepare("
                    INSERT INTO bot_ads (
                        campaign_name, ad_source_type, placement_zone, target_page_context,
                        target_category_id, language_iso, banner_text, audio_speech_text,
                        destination_url, network_script_code, click_cost, max_budget,
                        ad_billing_model, max_impressions, start_date, end_date, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param(
                        'ssssisssssddssisi',
                        $campaign_name, $ad_source_type, $placement_zone, $target_page_context,
                        $target_category_id, $language_iso, $banner_text, $audio_speech_text,
                        $destination_url, $network_script_code, $click_cost, $max_budget,
                        $ad_billing_model, $max_impressions, $start_date, $end_date, $is_active
                    );
                    if ($stmt->execute()) {
                        $success = "Campaign created successfully.";
                    } else {
                        $errors[] = "Database error: " . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "SQL error: " . $mysqli->error;
                }
            } else {
                // Edit action
                $id = (int)$_POST['id'];
                $stmt = $mysqli->prepare("
                    UPDATE bot_ads SET
                        campaign_name = ?, ad_source_type = ?, placement_zone = ?, target_page_context = ?,
                        target_category_id = ?, language_iso = ?, banner_text = ?, audio_speech_text = ?,
                        destination_url = ?, network_script_code = ?, click_cost = ?, max_budget = ?,
                        ad_billing_model = ?, max_impressions = ?, start_date = ?, end_date = ?, is_active = ?
                    WHERE id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param(
                        'ssssisssssddssisii',
                        $campaign_name, $ad_source_type, $placement_zone, $target_page_context,
                        $target_category_id, $language_iso, $banner_text, $audio_speech_text,
                        $destination_url, $network_script_code, $click_cost, $max_budget,
                        $ad_billing_model, $max_impressions, $start_date, $end_date, $is_active,
                        $id
                    );
                    if ($stmt->execute()) {
                        $success = "Campaign updated successfully.";
                    } else {
                        $errors[] = "Database error: " . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "SQL error: " . $mysqli->error;
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = "Invalid campaign selection.";
        } else {
            $stmt = $mysqli->prepare("DELETE FROM bot_ads WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $success = "Campaign deleted successfully.";
                } else {
                    $errors[] = "Database error: " . $mysqli->error;
                }
                $stmt->close();
            }
        }
    }
}

// Check edit state
$edit_campaign = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $mysqli->prepare("SELECT * FROM bot_ads WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $edit_campaign = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fetch all campaigns
$campaigns_res = $mysqli->query("
    SELECT ba.*, sc.name as category_name
    FROM bot_ads ba
    LEFT JOIN service_categories sc ON ba.target_category_id = sc.id
    ORDER BY ba.created_at DESC
");
$campaigns = [];
if ($campaigns_res) {
    while ($row = $campaigns_res->fetch_assoc()) {
        $campaigns[] = $row;
    }
}

// Fetch categories for targeted setting
$categories_res = $mysqli->query("SELECT id, name FROM service_categories ORDER BY name ASC");
$categories = [];
if ($categories_res) {
    while ($row = $categories_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 font-serif fw-bold">Site-Wide Monetization Ad Control Panel</h1>
            <p class="text-muted small mb-0">Control direct sponsor promotions, programmatic network campaigns, and real-time bid models.</p>
        </div>
    </div>

    <!-- Live Analytics Dashboard Widget -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 bg-white p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h4 class="card-title h5 font-serif fw-bold mb-1"><i class="bi bi-graph-up text-primary me-2"></i>Interactive Monetization Analytics</h4>
                <p class="text-muted small mb-0">Monitor total direct clicks, CTR, and cumulative revenue streams over time.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <input type="date" id="analytics_start" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                <span class="text-muted small">to</span>
                <input type="date" id="analytics_end" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                <button type="button" onclick="loadAdCharts()" class="btn btn-sm btn-primary px-3 rounded-pill">Refresh</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div style="height: 300px; position: relative;">
                    <canvas id="dailyRevenueChart"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div style="height: 300px; position: relative;">
                    <canvas id="campaignBreakdownChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success shadow-sm rounded-3"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm rounded-3">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- List section -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <strong class="h6 text-dark font-serif fw-bold">Active Direct & Network Campaigns</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($campaigns)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-advertisement fs-1 mb-3 text-secondary opacity-50"></i>
                            <div>No ad campaigns found. Complete the form to deploy your first ad promotion.</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Zone / Context</th>
                                        <th>Type & Model</th>
                                        <th>Spend / Budget</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($c['campaign_name']) ?></div>
                                                <span class="badge bg-secondary font-monospace" style="font-size: 0.7rem;"><?= htmlspecialchars($c['language_iso']) ?></span>
                                                <?php if (!empty($c['category_name'])): ?>
                                                    <span class="badge bg-info text-white" style="font-size: 0.7rem;"><?= htmlspecialchars($c['category_name']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="small">Zone: <code><?= htmlspecialchars($c['placement_zone']) ?></code></div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">Page: <code><?= htmlspecialchars($c['target_page_context']) ?></code></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $c['ad_source_type'] === 'direct_sponsor' ? 'primary' : 'warning' ?> text-uppercase" style="font-size: 0.7rem;">
                                                    <?= str_replace('_', ' ', $c['ad_source_type']) ?>
                                                </span>
                                                <div class="text-muted small font-monospace" style="font-size: 0.75rem;"><?= strtoupper($c['ad_billing_model']) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($c['ad_billing_model'] === 'flat_rate_temporal'): ?>
                                                    <span class="text-muted small">Flat Rate</span>
                                                    <div class="text-muted small font-monospace" style="font-size: 0.7rem;">
                                                        <?= $c['start_date'] ? date('M j', strtotime($c['start_date'])) : '*' ?> to
                                                        <?= $c['end_date'] ? date('M j', strtotime($c['end_date'])) : '*' ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div><strong>AED <?= number_format($c['current_spend'], 2) ?></strong></div>
                                                    <div class="text-muted small font-monospace" style="font-size: 0.7rem;">Limit: AED <?= number_format($c['max_budget'], 2) ?></div>
                                                <?php endif; ?>
                                                <div class="text-secondary small font-monospace" style="font-size: 0.7rem;">Imps: <?= number_format($c['current_impressions']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $c['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="btn-group">
                                                    <a href="?edit_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this ad campaign?')">
                                                        <?= csrf_field(); ?>
                                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Form section -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <strong class="h6 text-dark font-serif fw-bold"><?= $edit_campaign ? 'Edit Ad Campaign' : 'Create Ad Campaign' ?></strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                        <input type="hidden" name="action" value="<?= $edit_campaign ? 'edit' : 'create' ?>">
                        <?php if ($edit_campaign): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_campaign['id']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Campaign Name</label>
                            <input type="text" name="campaign_name" class="form-control" value="<?= htmlspecialchars($edit_campaign['campaign_name'] ?? '') ?>" placeholder="e.g. Burj Visa Promo" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Ad Source Type</label>
                            <select name="ad_source_type" id="ad_source_type" class="form-select" onchange="toggleFormFields()">
                                <option value="direct_sponsor" <?= ($edit_campaign['ad_source_type'] ?? 'direct_sponsor') === 'direct_sponsor' ? 'selected' : '' ?>>Direct Sponsor</option>
                                <option value="network_programmatic" <?= ($edit_campaign['ad_source_type'] ?? '') === 'network_programmatic' ? 'selected' : '' ?>>Network Programmatic (Fallback)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Placement Zone</label>
                            <select name="placement_zone" class="form-select">
                                <option value="bot_internal_chat" <?= ($edit_campaign['placement_zone'] ?? '') === 'bot_internal_chat' ? 'selected' : '' ?>>Bot Internal Chat</option>
                                <option value="site_header_leaderboard" <?= ($edit_campaign['placement_zone'] ?? 'site_header_leaderboard') === 'site_header_leaderboard' ? 'selected' : '' ?>>Site Header Leaderboard</option>
                                <option value="site_sidebar_banner" <?= ($edit_campaign['placement_zone'] ?? '') === 'site_sidebar_banner' ? 'selected' : '' ?>>Site Sidebar Banner</option>
                                <option value="site_footer_banner" <?= ($edit_campaign['placement_zone'] ?? '') === 'site_footer_banner' ? 'selected' : '' ?>>Site Footer Banner</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Target Page File Context</label>
                            <input type="text" name="target_page_context" class="form-control" value="<?= htmlspecialchars($edit_campaign['target_page_context'] ?? 'global_fallback') ?>" placeholder="e.g. vendor-profile.php or global_fallback" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Filename or <code>global_fallback</code></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Target Service Category</label>
                            <select name="target_category_id" class="form-select">
                                <option value="">-- No Category Constraint --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($edit_campaign['target_category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Language ISO Filter</label>
                            <select name="language_iso" class="form-select">
                                <option value="en" <?= ($edit_campaign['language_iso'] ?? 'en') === 'en' ? 'selected' : '' ?>>English (en)</option>
                                <option value="fr" <?= ($edit_campaign['language_iso'] ?? '') === 'fr' ? 'selected' : '' ?>>French (fr)</option>
                                <option value="ar" <?= ($edit_campaign['language_iso'] ?? '') === 'ar' ? 'selected' : '' ?>>Arabic (ar)</option>
                                <option value="ur" <?= ($edit_campaign['language_iso'] ?? '') === 'ur' ? 'selected' : '' ?>>Hindi/Urdu (ur)</option>
                            </select>
                        </div>

                        <!-- Direct Sponsor Fields Block -->
                        <div id="direct_sponsor_fields">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Banner Text (HTML Content)</label>
                                <input type="text" name="banner_text" class="form-control" value="<?= htmlspecialchars($edit_campaign['banner_text'] ?? '') ?>" placeholder="e.g. Fast Golden Visas with 100% Success">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Audio Speech Text (Bot TTS Voice)</label>
                                <input type="text" name="audio_speech_text" class="form-control" value="<?= htmlspecialchars($edit_campaign['audio_speech_text'] ?? '') ?>" placeholder="e.g. Sponsored: Burj Golden Visas offer priority delivery.">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Destination URL</label>
                                <input type="url" name="destination_url" class="form-control" value="<?= htmlspecialchars($edit_campaign['destination_url'] ?? '') ?>" placeholder="https://burjvisas.ae">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Billing Model Type</label>
                                <select name="ad_billing_model" id="ad_billing_model" class="form-select" onchange="toggleBillingFields()">
                                    <option value="ppc" <?= ($edit_campaign['ad_billing_model'] ?? 'ppc') === 'ppc' ? 'selected' : '' ?>>Pay-Per-Click (PPC)</option>
                                    <option value="ppi" <?= ($edit_campaign['ad_billing_model'] ?? '') === 'ppi' ? 'selected' : '' ?>>Pay-Per-Impression (PPI)</option>
                                    <option value="flat_rate_temporal" <?= ($edit_campaign['ad_billing_model'] ?? '') === 'flat_rate_temporal' ? 'selected' : '' ?>>Flat Rate Temporal (Date Controlled)</option>
                                </select>
                            </div>

                            <div class="row" id="financial_bidding_fields">
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">Click/Imp Cost (AED)</label>
                                    <input type="number" step="0.01" name="click_cost" class="form-control" value="<?= htmlspecialchars($edit_campaign['click_cost'] ?? '0.00') ?>">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">Budget Ceiling (AED)</label>
                                    <input type="number" step="0.01" name="max_budget" class="form-control" value="<?= htmlspecialchars($edit_campaign['max_budget'] ?? '0.00') ?>">
                                </div>
                            </div>

                            <div class="mb-3" id="impressions_limit_field">
                                <label class="form-label small fw-bold">Max Impressions (PPI Limit)</label>
                                <input type="number" name="max_impressions" class="form-control" value="<?= htmlspecialchars($edit_campaign['max_impressions'] ?? '0') ?>">
                                <small class="text-muted" style="font-size: 0.7rem;">0 = unlimited</small>
                            </div>

                            <div class="row" id="temporal_date_fields" style="display:none;">
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">Start Date</label>
                                    <input type="datetime-local" name="start_date" class="form-control" value="<?= !empty($edit_campaign['start_date']) ? date('Y-m-d\TH:i', strtotime($edit_campaign['start_date'])) : '' ?>">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">End Date</label>
                                    <input type="datetime-local" name="end_date" class="form-control" value="<?= !empty($edit_campaign['end_date']) ? date('Y-m-d\TH:i', strtotime($edit_campaign['end_date'])) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Network Programmatic Code Block -->
                        <div id="network_programmatic_fields" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Programmatic Script Code (Google AdSense/etc)</label>
                                <textarea name="network_script_code" class="form-control font-monospace" rows="6" placeholder="<script async src='https://pagead2.googlesyndication.com...'></script>"><?= htmlspecialchars($edit_campaign['network_script_code'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" name="is_active" id="isActiveCheck" class="form-check-input" <?= ($edit_campaign['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="isActiveCheck">Deploy Campaign (Is Active)</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow-sm"><?= $edit_campaign ? 'Save Changes' : 'Launch Campaign' ?></button>
                        <?php if ($edit_campaign): ?>
                            <a href="bot_ads.php" class="btn btn-link w-100 mt-2 text-center text-decoration-none text-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Load ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let dailyRevChart = null;
let campChart = null;

function toggleFormFields() {
    const sourceType = document.getElementById('ad_source_type').value;
    const directFields = document.getElementById('direct_sponsor_fields');
    const netFields = document.getElementById('network_programmatic_fields');

    if (sourceType === 'direct_sponsor') {
        directFields.style.display = 'block';
        netFields.style.display = 'none';
        toggleBillingFields();
    } else {
        directFields.style.display = 'none';
        netFields.style.display = 'block';
    }
}

function toggleBillingFields() {
    const billingModel = document.getElementById('ad_billing_model').value;
    const finFields = document.getElementById('financial_bidding_fields');
    const impField = document.getElementById('impressions_limit_field');
    const tempFields = document.getElementById('temporal_date_fields');

    if (billingModel === 'flat_rate_temporal') {
        finFields.style.display = 'none';
        impField.style.display = 'none';
        tempFields.style.display = 'flex';
    } else if (billingModel === 'ppi') {
        finFields.style.display = 'flex';
        impField.style.display = 'block';
        tempFields.style.display = 'none';
    } else {
        finFields.style.display = 'flex';
        impField.style.display = 'none';
        tempFields.style.display = 'none';
    }
}

function loadAdCharts() {
    const start = document.getElementById('analytics_start').value;
    const end = document.getElementById('analytics_end').value;

    fetch(`../../api/ad-revenue-charts.php?start_date=${start}&end_date=${end}`)
        .then(res => res.json())
        .then(payload => {
            if (payload.status === 'success') {
                renderDailyRevenueChart(payload.daily_breakdown);
                renderCampaignBreakdownChart(payload.campaign_breakdown);
            }
        })
        .catch(err => console.error("Error fetching ad reporting charts:", err));
}

function renderDailyRevenueChart(dailyData) {
    const ctx = document.getElementById('dailyRevenueChart').getContext('2d');
    if (dailyRevChart) {
        dailyRevChart.destroy();
    }

    dailyRevChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.labels,
            datasets: [{
                label: 'Cumulative Ad Revenue (AED)',
                data: dailyData.data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderCampaignBreakdownChart(campData) {
    const ctx = document.getElementById('campaignBreakdownChart').getContext('2d');
    if (campChart) {
        campChart.destroy();
    }

    if (campData.labels.length === 0) {
        // Render fallback message inside canvas wrapper or render empty doughnut
        campData.labels = ['No Data Available'];
        campData.data = [100];
    }

    campChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: campData.labels,
            datasets: [{
                data: campData.data,
                backgroundColor: [
                    '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
                    '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    toggleFormFields();
    loadAdCharts();
});
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>