<?php
// services/index.php
require_once __DIR__ . '/../lib/services_helpers.php';
require_once __DIR__ . '/../lib/providers_helpers.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$filters = [];
if (!empty($_GET['q'])) $filters['q'] = trim($_GET['q']);
if (!empty($_GET['category_id'])) $filters['category_id'] = intval($_GET['category_id']);

$services = services_paginated($page, $perPage, $filters);
$categories = service_categories_all();

include __DIR__ . '/../partials/header.php';
?>
<div class="container mt-4">
  <h3>Services</h3>
  <div class="row">
    <div class="col-md-8">
      <?php foreach ($services as $s): ?>
        <div class="card mb-3 p-3">
          <div class="d-flex">
            <?php $imgs = json_decode($s['images'] ?? '[]', true) ?: []; ?>
            <img src="<?php echo htmlspecialchars($imgs[0] ?? '/public/assets/img/service-placeholder.png', ENT_QUOTES); ?>" style="width:120px;height:80px;object-fit:cover;margin-right:12px;">
            <div>
              <h5><a href="/services/view.php?slug=<?php echo urlencode($s['slug']); ?>"><?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?></a></h5>
              <div class="text-muted"><?php echo htmlspecialchars($s['provider_name'], ENT_QUOTES); ?> — <?php echo htmlspecialchars($s['category_name'] ?? '', ENT_QUOTES); ?></div>
              <div><?php echo $s['price'] !== null ? htmlspecialchars($s['currency'] . ' ' . $s['price'], ENT_QUOTES) : 'Price on request'; ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="col-md-4">
      <h6>Categories</h6>
      <ul>
        <?php foreach ($categories as $c): ?>
          <li><a href="/services/index.php?category_id=<?php echo intval($c['id']); ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
