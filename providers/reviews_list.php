<?php
// providers/reviews_list.php
require_once __DIR__ . '/../lib/middleware.php';
require_once __DIR__ . '/../lib/reviews_helpers.php';
require_once __DIR__ . '/../lib/providers_helpers.php';

$provider_id = intval($_GET['provider_id'] ?? 0);
if ($provider_id <= 0) { http_response_code(400); echo 'Invalid provider'; exit; }

$page = max(1, intval($_GET['page'] ?? 1));
$res = review_list(['provider_id' => $provider_id, 'page' => $page, 'per_page' => 10, 'status' => 'published']);
$provider = provider_find($provider_id);

include __DIR__ . '/../partials/header.php';
?>
<div class="card mt-4 p-3">
  <h4>Reviews for <?php echo htmlspecialchars($provider['name'] ?? 'Provider', ENT_QUOTES); ?></h4>

  <?php if (!$res['ok'] || empty($res['reviews'])): ?>
    <div class="alert alert-info">No reviews yet.</div>
  <?php else: ?>
    <?php foreach ($res['reviews'] as $r): ?>
      <div class="mb-3 border-bottom pb-2">
        <div class="d-flex justify-content-between">
          <div><strong><?php echo htmlspecialchars($r['user_name'] ?? 'User', ENT_QUOTES); ?></strong></div>
          <div><?php echo str_repeat('★', intval($r['rating'])) . str_repeat('☆', 5 - intval($r['rating'])); ?></div>
        </div>
        <?php if (!empty($r['title'])): ?><div class="fw-semibold"><?php echo htmlspecialchars($r['title'], ENT_QUOTES); ?></div><?php endif; ?>
        <div><?php echo nl2br(htmlspecialchars($r['body'], ENT_QUOTES)); ?></div>
        <div class="text-muted small">Posted <?php echo htmlspecialchars($r['created_at'], ENT_QUOTES); ?></div>
      </div>
    <?php endforeach; ?>

    <?php $pages = (int)ceil($res['total'] / $res['per_page']); if ($pages > 1): ?>
      <nav><ul class="pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
          <li class="page-item <?php echo $i === $res['page'] ? 'active' : ''; ?>"><a class="page-link" href="?provider_id=<?php echo $provider_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>
      </ul></nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php
// include provider review submission form if logged in
if (!empty($_SESSION['user_id'])) {
    include __DIR__ . '/review_form_provider_snippet.php';
}
?>

<?php include __DIR__ . '/../partials/footer.php'; ?>
