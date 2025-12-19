<?php
declare(strict_types=1);

// Заголовок блока
$blockTitle = isset($blockTitle) && is_string($blockTitle) && trim($blockTitle) !== ''
    ? $blockTitle
    : 'Меню';

// Элементы меню (можно передать из контроллера/регистратора модуля)
$items = $items ?? [
    ['label' => 'Dashboard', 'url' => site_url('admin')],
    ['label' => 'Домены',    'url' => site_url('admin/domains')],
    ['label' => 'Ящики',     'url' => site_url('admin/mailboxes')],
    ['label' => 'Алиасы',    'url' => site_url('admin/aliases')],
    ['label' => 'Настройки', 'url' => site_url('admin/settings')],
];

// Текущий URL для подсветки активного пункта
$current = (string) current_url();

// Безопасная нормализация массива
if (!is_array($items)) {
    $items = [];
}
?>
<div class="card shadow-sm">
    <div class="card-header py-2">
        <strong><?= esc($blockTitle) ?></strong>
    </div>

    <div class="list-group list-group-flush">
        <?php foreach ($items as $it): ?>
            <?php
            if (!is_array($it)) continue;

            $label = (string) ($it['label'] ?? '');
            $url   = (string) ($it['url'] ?? '#');

            if ($label === '' || $url === '') continue;

            // Простая подсветка активного: текущий URL начинается с url пункта
            $isActive = ($url !== '#') && str_starts_with($current, $url);
            ?>
            <a href="<?= esc($url) ?>"
               class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>">
                <?= esc($label) ?>
            </a>
        <?php endforeach; ?>

        <?php if ($items === []): ?>
            <div class="list-group-item text-muted">Нет пунктов меню</div>
        <?php endif; ?>
    </div>
</div>
