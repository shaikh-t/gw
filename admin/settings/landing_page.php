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

    <form action="<?php echo $domain;?>/admin/settings/update.php" method="POST" enctype="multipart/form-data">
        <?= csrf_field(); ?>

        <div class="card mb-4">
            <div class="card-header bg-white"><strong>Hero Section</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= $settings['hero_title_gradient']['label'] ?></label>
                        <input type="text" name="settings[hero_title_gradient]" class="form-control" value="<?= htmlspecialchars($settings['hero_title_gradient']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= $settings['hero_title_rest']['label'] ?></label>
                        <input type="text" name="settings[hero_title_rest]" class="form-control" value="<?= htmlspecialchars($settings['hero_title_rest']['value'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $settings['hero_title']['label'] ?> (Fallback/Legacy)</label>
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
            <div class="card-header bg-white"><strong>Stats & Trust Bar Section</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><strong><?= htmlspecialchars($settings['trust_bar_partners']['label'] ?? 'Trust Bar Partners') ?></strong> (Comma-separated names)</label>
                    <input type="text" name="settings[trust_bar_partners]" class="form-control" value="<?= htmlspecialchars($settings['trust_bar_partners']['value'] ?? '') ?>">
                </div>

                <hr>
                <h5 class="mb-3">Rich Stats Section Heading</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= htmlspecialchars($settings['stat_result_label']['label'] ?? 'Stat Section Label') ?></label>
                        <input type="text" name="settings[stat_result_label]" class="form-control" value="<?= htmlspecialchars($settings['stat_result_label']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= htmlspecialchars($settings['stat_result_heading_gradient']['label'] ?? 'Stat Heading Gradient Word') ?></label>
                        <input type="text" name="settings[stat_result_heading_gradient]" class="form-control" value="<?= htmlspecialchars($settings['stat_result_heading_gradient']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= htmlspecialchars($settings['stat_result_heading_rest']['label'] ?? 'Stat Heading Rest') ?></label>
                        <input type="text" name="settings[stat_result_heading_rest]" class="form-control" value="<?= htmlspecialchars($settings['stat_result_heading_rest']['value'] ?? '') ?>">
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Rich Stat Cards</h5>

                <div class="row mb-3">
                    <h6>Stat Card 1 (Mint)</h6>
                    <div class="col-md-3">
                        <label class="form-label">Number / Value</label>
                        <input type="text" name="settings[stat_card1_number]" class="form-control" value="<?= htmlspecialchars($settings['stat_card1_number']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="settings[stat_card1_label]" class="form-control" value="<?= htmlspecialchars($settings['stat_card1_label']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <input type="text" name="settings[stat_card1_desc]" class="form-control" value="<?= htmlspecialchars($settings['stat_card1_desc']['value'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <h6>Stat Card 2 (Warm)</h6>
                    <div class="col-md-3">
                        <label class="form-label">Number / Value</label>
                        <input type="text" name="settings[stat_card2_number]" class="form-control" value="<?= htmlspecialchars($settings['stat_card2_number']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="settings[stat_card2_label]" class="form-control" value="<?= htmlspecialchars($settings['stat_card2_label']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <input type="text" name="settings[stat_card2_desc]" class="form-control" value="<?= htmlspecialchars($settings['stat_card2_desc']['value'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <h6>Stat Card 3 (Dark)</h6>
                    <div class="col-md-3">
                        <label class="form-label">Number / Value</label>
                        <input type="text" name="settings[stat_card3_number]" class="form-control" value="<?= htmlspecialchars($settings['stat_card3_number']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="settings[stat_card3_label]" class="form-control" value="<?= htmlspecialchars($settings['stat_card3_label']['value'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <input type="text" name="settings[stat_card3_desc]" class="form-control" value="<?= htmlspecialchars($settings['stat_card3_desc']['value'] ?? '') ?>">
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Legacy Stats (Fallback)</h5>
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
