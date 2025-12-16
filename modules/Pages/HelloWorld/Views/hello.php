<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Hello') ?></title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 24px;">
  <h1><?= esc($title ?? 'Hello World') ?></h1>
  <p>Module: <b>Pages/HelloWorld</b></p>
  <p>Rendered at: <b><?= esc($time ?? '') ?></b></p>
  <p>Try route alias (для проверки генерации URL): <code><?= esc(route_to('helloworld.index')) ?></code></p>
</body>
</html>
