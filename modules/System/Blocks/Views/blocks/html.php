<div class="card shadow-sm mb-3">
  <?php if (!empty($title)): ?>
    <div class="card-header bg-white fw-semibold"><?= esc($title) ?></div>
  <?php endif; ?>
  <div class="card-body">
    <?= $html ?>
  </div>
</div>
