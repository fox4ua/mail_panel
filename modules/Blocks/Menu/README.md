# Blocks/Menu module (block output)

This module provides a block view `blocks/Menu:index` for your Layout renderer.

## Usage (example)
Assuming your LayoutModuleManager calls something like:

- module id: `blocks/Menu:index`
- parameters include menu key

Prepare data before rendering view:
- Use `Modules\Blocks\Menu\Libraries\MenuBlock`

Example in your module manager:
```php
$block = new \Modules\Blocks\Menu\Libraries\MenuBlock();
$data  = $block->build('main', [
  'ul_class' => 'nav flex-column',
  'a_class'  => 'nav-link',
]);
return view('Modules\\Blocks\\Menu\\Views\\index', $data);
```

The DB and management UI are in System/Menu module.
