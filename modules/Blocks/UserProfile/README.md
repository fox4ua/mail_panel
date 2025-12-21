# Blocks/UserProfile module (header block)

Block view: `blocks/UserProfile:header`

## How to render
Your Layout renderer/module manager should:
- instantiate `Modules\Blocks\UserProfile\Libraries\UserProfileHeaderBlock`
- call `build()`
- render view `modules/Blocks/UserProfile/Views/header.php`

Example:
```php
$block = new \Modules\Blocks\UserProfile\Libraries\UserProfileHeaderBlock();
$data  = $block->build([
  'profile_url' => site_url('account/profile'),
  'logout_url'  => site_url('logout'),
]);
return view('Modules\\Blocks\\UserProfile\\Views\\header', $data);
```

## Dependencies
Relies on System/Profile module:
- CurrentUser (session integration)
- ProfileService + models
