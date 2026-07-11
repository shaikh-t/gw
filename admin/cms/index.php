<?php
// admin/cms/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('cms.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$active_tab = $_GET['tab'] ?? 'about';

// Fetch the selected page content
$res = $mysqli->query("SELECT content FROM cms_pages WHERE page_name = '" . $mysqli->real_escape_string($active_tab) . "' LIMIT 1");
$content = [];
if ($res && $row = $res->fetch_assoc()) {
    $content = json_decode($row['content'], true) ?: [];
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="container-fluid mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>CMS Page Management</h2>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php
      if (is_array($_SESSION['flash_errors'])) {
          foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e) . '<br>';
      } else {
          echo htmlspecialchars($_SESSION['flash_errors']);
      }
      unset($_SESSION['flash_errors']);
      ?>
    </div>
  <?php endif; ?>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $active_tab === 'about' ? 'active font-weight-bold' : '' ?>" href="?tab=about">About Us Page</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $active_tab === 'contact' ? 'active font-weight-bold' : '' ?>" href="?tab=contact">Contact Page</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $active_tab === 'how_it_works' ? 'active font-weight-bold' : '' ?>" href="?tab=how_it_works">How It Works Page</a>
    </li>
  </ul>

  <div class="card p-4 bg-white">
    <form action="update.php" method="POST">
      <?= csrf_field(); ?>
      <input type="hidden" name="page_name" value="<?= htmlspecialchars($active_tab) ?>">

      <?php if ($active_tab === 'about'): ?>
        <!-- ABOUT US FORM -->
        <h5 class="border-bottom pb-2 mb-3 text-primary">Hero Section</h5>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Story Kicker</label>
            <input type="text" name="content[story_kicker]" class="form-control" value="<?= htmlspecialchars($content['story_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Story Title</label>
            <input type="text" name="content[story_title]" class="form-control" value="<?= htmlspecialchars($content['story_title'] ?? '') ?>" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Story Description / Subtitle</label>
            <textarea name="content[story_sub]" class="form-control" rows="3" required><?= htmlspecialchars($content['story_sub'] ?? '') ?></textarea>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">CTA Text</label>
            <input type="text" name="content[story_cta_text]" class="form-control" value="<?= htmlspecialchars($content['story_cta_text'] ?? '') ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">CTA URL</label>
            <input type="text" name="content[story_cta_url]" class="form-control" value="<?= htmlspecialchars($content['story_cta_url'] ?? '') ?>">
          </div>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Statistics</h5>
        <div class="row">
          <?php for($i = 0; $i < 4; $i++):
              $stat = $content['stats'][$i] ?? ['number' => '', 'label' => '', 'highlight' => false];
          ?>
            <div class="col-md-3 mb-3 border-end">
              <h6>Stat Card <?= $i + 1 ?></h6>
              <div class="mb-2">
                <label class="form-label small">Number / Value</label>
                <input type="text" name="content[stats][<?= $i ?>][number]" class="form-control form-control-sm" value="<?= htmlspecialchars($stat['number'] ?? '') ?>" required>
              </div>
              <div class="mb-2">
                <label class="form-label small">Label</label>
                <input type="text" name="content[stats][<?= $i ?>][label]" class="form-control form-control-sm" value="<?= htmlspecialchars($stat['label'] ?? '') ?>" required>
              </div>
              <div class="form-check">
                <input type="checkbox" name="content[stats][<?= $i ?>][highlight]" value="1" class="form-check-input" id="stat_h_<?= $i ?>" <?= !empty($stat['highlight']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="stat_h_<?= $i ?>">Highlight card background</label>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Mission Section</h5>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Mission Kicker</label>
            <input type="text" name="content[mission_kicker]" class="form-control" value="<?= htmlspecialchars($content['mission_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Mission Title</label>
            <input type="text" name="content[mission_title]" class="form-control" value="<?= htmlspecialchars($content['mission_title'] ?? '') ?>" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Mission Paragraph</label>
            <textarea name="content[mission_copy]" class="form-control" rows="4" required><?= htmlspecialchars($content['mission_copy'] ?? '') ?></textarea>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Proof Points (one per line)</label>
            <textarea name="mission_proof_text" class="form-control" rows="5" required><?= htmlspecialchars(implode("\n", $content['mission_proof'] ?? [])) ?></textarea>
            <small class="text-muted">Enter list items of achievements to be shown with checkmarks.</small>
          </div>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Core Values</h5>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Values Section Kicker</label>
            <input type="text" name="content[values_kicker]" class="form-control" value="<?= htmlspecialchars($content['values_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Values Section Title</label>
            <input type="text" name="content[values_title]" class="form-control" value="<?= htmlspecialchars($content['values_title'] ?? '') ?>" required>
          </div>
          <?php for($i = 0; $i < 4; $i++):
              $val = $content['values'][$i] ?? ['icon' => 'bi-shield', 'title' => '', 'desc' => ''];
          ?>
            <div class="col-md-6 mb-3">
              <div class="card p-3 bg-light">
                <h6>Value <?= $i + 1 ?></h6>
                <div class="mb-2">
                  <label class="form-label small">Bootstrap Icon Class</label>
                  <input type="text" name="content[values][<?= $i ?>][icon]" class="form-control form-control-sm" value="<?= htmlspecialchars($val['icon'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Title</label>
                  <input type="text" name="content[values][<?= $i ?>][title]" class="form-control form-control-sm" value="<?= htmlspecialchars($val['title'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Description</label>
                  <textarea name="content[values][<?= $i ?>][desc]" class="form-control form-control-sm" rows="2" required><?= htmlspecialchars($val['desc'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Journey / Timeline</h5>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Journey Section Kicker</label>
            <input type="text" name="content[journey_kicker]" class="form-control" value="<?= htmlspecialchars($content['journey_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Journey Section Title</label>
            <input type="text" name="content[journey_title]" class="form-control" value="<?= htmlspecialchars($content['journey_title'] ?? '') ?>" required>
          </div>
          <?php for($i = 0; $i < 6; $i++):
              $step = $content['journey'][$i] ?? ['year' => '', 'title' => '', 'desc' => ''];
          ?>
            <div class="col-md-4 mb-3">
              <div class="card p-3 bg-light">
                <h6>Timeline Milestone <?= $i + 1 ?></h6>
                <div class="row">
                  <div class="col-sm-4 mb-2">
                    <label class="form-label small">Year</label>
                    <input type="text" name="content[journey][<?= $i ?>][year]" class="form-control form-control-sm" value="<?= htmlspecialchars($step['year'] ?? '') ?>" required>
                  </div>
                  <div class="col-sm-8 mb-2">
                    <label class="form-label small">Title</label>
                    <input type="text" name="content[journey][<?= $i ?>][title]" class="form-control form-control-sm" value="<?= htmlspecialchars($step['title'] ?? '') ?>" required>
                  </div>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Description</label>
                  <textarea name="content[journey][<?= $i ?>][desc]" class="form-control form-control-sm" rows="2" required><?= htmlspecialchars($step['desc'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>

      <?php elseif ($active_tab === 'contact'): ?>
        <!-- CONTACT US FORM -->
        <h5 class="border-bottom pb-2 mb-3 text-primary">Hero Section</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Hero Kicker</label>
            <input type="text" name="content[hero_kicker]" class="form-control" value="<?= htmlspecialchars($content['hero_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-8 mb-3">
            <label class="form-label">Hero Title</label>
            <input type="text" name="content[hero_title]" class="form-control" value="<?= htmlspecialchars($content['hero_title'] ?? '') ?>" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Hero Description</label>
            <textarea name="content[hero_sub]" class="form-control" rows="3" required><?= htmlspecialchars($content['hero_sub'] ?? '') ?></textarea>
          </div>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Support Channels</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">WhatsApp Number / Label</label>
            <input type="text" name="content[whatsapp]" class="form-control mb-2" placeholder="+971 4 400 0000" value="<?= htmlspecialchars($content['whatsapp'] ?? '') ?>" required>
            <label class="form-label small text-muted">WhatsApp URL (e.g. https://wa.me/97144000000)</label>
            <input type="text" name="content[whatsapp_url]" class="form-control mb-2" value="<?= htmlspecialchars($content['whatsapp_url'] ?? '') ?>" required>
            <label class="form-label small text-muted">Meta text (e.g. Avg. reply · under 30 min)</label>
            <input type="text" name="content[whatsapp_meta]" class="form-control" value="<?= htmlspecialchars($content['whatsapp_meta'] ?? '') ?>" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Support Email</label>
            <input type="email" name="content[email]" class="form-control mb-2" value="<?= htmlspecialchars($content['email'] ?? '') ?>" required>
            <label class="form-label small text-muted">Meta text (e.g. Avg. reply · under 2 hours)</label>
            <input type="text" name="content[email_meta]" class="form-control" value="<?= htmlspecialchars($content['email_meta'] ?? '') ?>" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" name="content[phone]" class="form-control mb-2" value="<?= htmlspecialchars($content['phone'] ?? '') ?>" required>
            <label class="form-label small text-muted">Meta text (e.g. Sun-Thu · 8:00 AM - 6:00 PM GST)</label>
            <input type="text" name="content[phone_meta]" class="form-control" value="<?= htmlspecialchars($content['phone_meta'] ?? '') ?>" required>
          </div>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Headquarters</h5>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">HQ Location Title (e.g. Dubai, UAE)</label>
            <input type="text" name="content[hq_title]" class="form-control" value="<?= htmlspecialchars($content['hq_title'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">HQ Address</label>
            <textarea name="content[hq_address]" class="form-control" rows="3" required><?= htmlspecialchars($content['hq_address'] ?? '') ?></textarea>
          </div>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Office Business Hours</h5>
        <div class="row">
          <?php for($i = 0; $i < 3; $i++):
              $hr = $content['hours'][$i] ?? ['days' => '', 'time' => '', 'closed' => false];
          ?>
            <div class="col-md-4 mb-3">
              <div class="card p-3 bg-light">
                <h6>Time Block <?= $i + 1 ?></h6>
                <div class="mb-2">
                  <label class="form-label small">Days (e.g. Sun – Thu)</label>
                  <input type="text" name="content[hours][<?= $i ?>][days]" class="form-control form-control-sm" value="<?= htmlspecialchars($hr['days'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Hours (e.g. 8:00 AM – 6:00 PM GST)</label>
                  <input type="text" name="content[hours][<?= $i ?>][time]" class="form-control form-control-sm" value="<?= htmlspecialchars($hr['time'] ?? '') ?>" required>
                </div>
                <div class="form-check">
                  <input type="checkbox" name="content[hours][<?= $i ?>][closed]" value="1" class="form-check-input" id="hr_closed_<?= $i ?>" <?= !empty($hr['closed']) ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="hr_closed_<?= $i ?>">Closed / Rest day</label>
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>

      <?php elseif ($active_tab === 'how_it_works'): ?>
        <!-- HOW IT WORKS FORM -->
        <h5 class="border-bottom pb-2 mb-3 text-primary">Hero Section</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Hero Kicker</label>
            <input type="text" name="content[hero_kicker]" class="form-control" value="<?= htmlspecialchars($content['hero_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-8 mb-3">
            <label class="form-label">Hero Title</label>
            <input type="text" name="content[hero_title]" class="form-control" value="<?= htmlspecialchars($content['hero_title'] ?? '') ?>" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Hero Description</label>
            <textarea name="content[hero_sub]" class="form-control" rows="3" required><?= htmlspecialchars($content['hero_sub'] ?? '') ?></textarea>
          </div>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">The 4 Simple Steps</h5>
        <?php for($i = 0; $i < 4; $i++):
            $step = $content['steps'][$i] ?? ['num' => sprintf('%02d', $i+1), 'icon' => '', 'title' => '', 'desc' => '', 'bullets' => []];
        ?>
          <div class="card p-3 mb-3 bg-light">
            <h6>Step <?= $i + 1 ?> (<?= htmlspecialchars($step['num']) ?>)</h6>
            <div class="row">
              <input type="hidden" name="content[steps][<?= $i ?>][num]" value="<?= htmlspecialchars($step['num']) ?>">
              <div class="col-md-4 mb-2">
                <label class="form-label small">Bootstrap Icon (e.g. bi-search)</label>
                <input type="text" name="content[steps][<?= $i ?>][icon]" class="form-control form-control-sm" value="<?= htmlspecialchars($step['icon'] ?? '') ?>" required>
              </div>
              <div class="col-md-8 mb-2">
                <label class="form-label small">Step Title</label>
                <input type="text" name="content[steps][<?= $i ?>][title]" class="form-control form-control-sm" value="<?= htmlspecialchars($step['title'] ?? '') ?>" required>
              </div>
              <div class="col-12 mb-2">
                <label class="form-label small">Description</label>
                <textarea name="content[steps][<?= $i ?>][desc]" class="form-control form-control-sm" rows="2" required><?= htmlspecialchars($step['desc'] ?? '') ?></textarea>
              </div>
              <div class="col-12 mb-2">
                <label class="form-label small">Bullet Points (one per line)</label>
                <textarea name="steps_bullets_text_<?= $i ?>" class="form-control form-control-sm" rows="3" required><?= htmlspecialchars(implode("\n", $step['bullets'] ?? [])) ?></textarea>
              </div>
            </div>
          </div>
        <?php endfor; ?>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Vendor Join Section</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Vendor Section Kicker</label>
            <input type="text" name="content[vendor_kicker]" class="form-control" value="<?= htmlspecialchars($content['vendor_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-8 mb-3">
            <label class="form-label">Vendor Section Title</label>
            <input type="text" name="content[vendor_title]" class="form-control" value="<?= htmlspecialchars($content['vendor_title'] ?? '') ?>" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Vendor Subtitle</label>
            <textarea name="content[vendor_sub]" class="form-control" rows="2" required><?= htmlspecialchars($content['vendor_sub'] ?? '') ?></textarea>
          </div>
          <?php for($i = 0; $i < 4; $i++):
              $v_step = $content['vendor_steps'][$i] ?? ['icon' => '', 'step' => 'STEP ' . ($i+1), 'title' => '', 'desc' => ''];
          ?>
            <div class="col-md-6 mb-3">
              <div class="card p-3 bg-light">
                <h6>Vendor Step <?= $i + 1 ?></h6>
                <div class="row">
                  <input type="hidden" name="content[vendor_steps][<?= $i ?>][step]" value="<?= htmlspecialchars($v_step['step']) ?>">
                  <div class="col-md-4 mb-2">
                    <label class="form-label small">Icon (e.g. bi-cloud-arrow-up)</label>
                    <input type="text" name="content[vendor_steps][<?= $i ?>][icon]" class="form-control form-control-sm" value="<?= htmlspecialchars($v_step['icon'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-8 mb-2">
                    <label class="form-label small">Title</label>
                    <input type="text" name="content[vendor_steps][<?= $i ?>][title]" class="form-control form-control-sm" value="<?= htmlspecialchars($v_step['title'] ?? '') ?>" required>
                  </div>
                  <div class="col-12 mb-2">
                    <label class="form-label small">Description</label>
                    <textarea name="content[vendor_steps][<?= $i ?>][desc]" class="form-control form-control-sm" rows="2" required><?= htmlspecialchars($v_step['desc'] ?? '') ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <h5 class="border-bottom pb-2 mb-3 mt-4 text-primary">Call to Action</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">CTA Kicker</label>
            <input type="text" name="content[cta_kicker]" class="form-control" value="<?= htmlspecialchars($content['cta_kicker'] ?? '') ?>" required>
          </div>
          <div class="col-md-8 mb-3">
            <label class="form-label">CTA Title</label>
            <input type="text" name="content[cta_title]" class="form-control" value="<?= htmlspecialchars($content['cta_title'] ?? '') ?>" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">CTA Subtitle</label>
            <textarea name="content[cta_sub]" class="form-control" rows="2" required><?= htmlspecialchars($content['cta_sub'] ?? '') ?></textarea>
          </div>
        </div>

      <?php endif; ?>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary px-5">Save CMS Page</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
