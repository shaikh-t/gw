<?php
// providers/review_form_provider_snippet.php
if (empty($provider_id)) $provider_id = intval($_GET['provider_id'] ?? 0);
?>
<div class="card mt-3 p-3">
  <h5>Write a review for this provider</h5>
  <form method="post" action="/providers/review_submit.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="provider_id" value="<?php echo intval($provider_id); ?>">
    <div class="mb-3">
      <label class="form-label">Rating</label>
      <select name="rating" class="form-select" required>
        <option value="">-- choose --</option>
        <option value="5">5 — Excellent</option>
        <option value="4">4 — Very good</option>
        <option value="3">3 — Good</option>
        <option value="2">2 — Fair</option>
        <option value="1">1 — Poor</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Title (optional)</label>
      <input name="title" class="form-control" maxlength="255">
    </div>
    <div class="mb-3">
      <label class="form-label">Review</label>
      <textarea name="body" class="form-control" rows="5" required></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Anti-spam check</label>
      <div class="form-text">Type the word <strong>human</strong> to pass the quick check.</div>
      <input name="captcha_answer" class="form-control" required>
    </div>

    <button class="btn btn-primary">Submit review</button>
  </form>
</div>
