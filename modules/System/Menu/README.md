# System/Menu module (CI4 HMVC)

Provides admin UI to manage menus and menu items in DB.

## Tables
- `menus`
- `menu_items`

## URLs
- /admin/system/menus
- /admin/system/menus/{menuId}/items

Routes file:
- modules/System/Menu/Config/Routes.php

## Notes
- Uses CI4 Model methods only (insert/update/delete/findAll).
- No manual DB transactions.
- Items support nesting via parent_id and ordering via sort_order.
