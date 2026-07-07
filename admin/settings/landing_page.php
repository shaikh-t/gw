<?php
// admin/settings/landing_page.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

// Fetch all settings
$res = $mysqli->query("SELECT * FROM site_settings ORDER BY id ASC");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['key']] = $row;
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Website Settings</h2>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>

    <form action="/admin/settings/update.php" method="POST" enctype="multipart/form-data">
        <?= csrf_field(); ?>

        <div class="card mb-4">
            <div class="card-header bg-white"><strong>Hero Section</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><?= $settings['hero_title']['label'] ?></label>
                    <input type="text" name="settings[hero_title]" class="form-control" value="<?= htmlspecialchars($settings['hero_title']['value']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $settings['hero_subtitle']['label'] ?></label>
                    <textarea name="settings[hero_subtitle]" class="form-control" rows="3"><?= htmlspecialchars($settings['hero_subtitle']['value']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $settings['hero_bg_image']['label'] ?> (URL or Upload)</label>
                    <input type="text" name="settings[hero_bg_image]" class="form-control mb-2" value="<?= htmlspecialchars($settings['hero_bg_image']['value']) ?>">
                    <input type="file" name="hero_bg_file" class="form-control">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white"><strong>Stats Bar</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= $settings['stat_vendors']['label'] ?></label>
                        <input type="text" name="settings[stat_vendors]" class="form-control" value="<?= htmlspecialchars($settings['stat_vendors']['value']) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= $settings['stat_cases']['label'] ?></label>
                        <input type="text" name="settings[stat_cases]" class="form-control" value="<?= htmlspecialchars($settings['stat_cases']['value']) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= $settings['stat_success_rate']['label'] ?></label>
                        <input type="text" name="settings[stat_success_rate]" class="form-control" value="<?= htmlspecialchars($settings['stat_success_rate']['value']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white"><strong>CTA Banner (Bottom)</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><?= $settings['cta_banner_title']['label'] ?></label>
                    <input type="text" name="settings[cta_banner_title]" class="form-control" value="<?= htmlspecialchars($settings['cta_banner_title']['value']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $settings['cta_banner_bg']['label'] ?> (URL or Upload)</label>
                    <input type="text" name="settings[cta_banner_bg]" class="form-control mb-2" value="<?= htmlspecialchars($settings['cta_banner_bg']['value']) ?>">
                    <input type="file" name="cta_banner_file" class="form-control">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white"><strong>Social Media URLs</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Facebook</label>
                        <input type="text" name="settings[social_facebook]" class="form-control" value="<?= htmlspecialchars($settings['social_facebook']['value']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">LinkedIn</label>
                        <input type="text" name="settings[social_linkedin]" class="form-control" value="<?= htmlspecialchars($settings['social_linkedin']['value']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Instagram</label>
                        <input type="text" name="settings[social_instagram]" class="form-control" value="<?= htmlspecialchars($settings['social_instagram']['value']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Behance</label>
                        <input type="text" name="settings[social_behance]" class="form-control" value="<?= htmlspecialchars($settings['social_behance']['value']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white"><strong>Contact & Footer</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="settings[contact_email]" class="form-control" value="<?= htmlspecialchars($settings['contact_email']['value']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Headquarters Address</label>
                    <textarea name="settings[contact_address]" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_address']['value']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer Disclaimer</label>
                    <textarea name="settings[footer_disclaimer]" class="form-control" rows="4"><?= htmlspecialchars($settings['footer_disclaimer']['value']) ?></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-5">Save All Settings</button>
    </form>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
