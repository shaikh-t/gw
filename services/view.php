<?php
// services/view.php
require_once __DIR__ . '/../lib/services_helpers.php';
$slug = $_GET['slug'] ?? '';
if ($slug === '') { http_response_code(404); echo 'Not found'; exit; }
$service = service_find($slug);
if (!$service) { http_response_code(404); echo 'Service not found'; exit; }
include __DIR__ . '/../partials/header.php';
?>
<div class="container mt-4">
  <div class="card p-3">
    <div class="d-flex">
      <?php $imgs = $service['images'] ?: []; ?>
      <img src="<?php echo htmlspecialchars($imgs[0] ?? '/public/assets/img/service-placeholder.png', ENT_QUOTES); ?>" style="width:180px;height:120px;object-fit:cover;margin-right:16px;">
      <div>
        <h2><?php echo htmlspecialchars($service['title'], ENT_QUOTES); ?></h2>
        <div class="text-muted"><?php echo htmlspecialchars($service['provider_name'], ENT_QUOTES); ?></div>
        <div class="mt-2"><?php echo $service['price'] !== null ? htmlspecialchars($service['currency'] . ' ' . $service['price'], ENT_QUOTES) : 'Price on request'; ?></div>
      </div>
    </div>

    <hr>
    <div>
      <h5>About this service</h5>
      <p><?php echo nl2br(htmlspecialchars($service['description'] ?? '', ENT_QUOTES)); ?></p>
    </div>

    <div class="mt-3">
      <a href="/book?service=<?php echo intval($service['id']); ?>" class="btn btn-primary">Book this service</a>
      <a href="/providers/view.php?slug=<?php echo urlencode($service['provider_slug'] ?? ''); ?>" class="btn btn-link">View provider</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
