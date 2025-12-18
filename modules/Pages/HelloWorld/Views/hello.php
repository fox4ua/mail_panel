<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <div class="d-flex align-items-center gap-2 mb-2">
      <span class="badge text-bg-primary">Module</span>
      <span class="badge text-bg-light">Pages/HelloWorld</span>
    </div>

    <h3 class="mb-1">Hello, <?= esc($name ?? '') ?></h3>
    <div class="text-muted">
      Это view загружено автоматически из текущего модуля и вставлено в общий шаблон.
    </div>
  </div>
</div>
