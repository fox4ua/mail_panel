<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'App') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <?= $render->renderCss() ?>
  <?= $render->renderJs('head') ?>
</head>
<body class="bg-light">

<nav class="navbar navbar-light bg-white border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand" href="/">App</a>
  </div>
</nav>

<div class="container-fluid py-3">
  <?= $content ?? '' ?>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $render->renderJs('body') ?>
</body>
</html>
