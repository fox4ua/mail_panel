# System/Profile module (CI4 HMVC)

Provides:
- Page: **My profile** (edit current user's profile)
- Storage: `user_profile` (keyed by `user_id`)
- Designed to work with users table `user` (read-only in this module)

## URLs
- GET  /account/profile
- POST /account/profile/save

> Filter in Routes.php is set to `auth` by default — replace with your project filter.

## Required tables

This module expects:
- `user` table with primary key `id` (UUID or INT)
- `user_profile` table with fields:
  - id (AUTO_INCREMENT)
  - user_id (VARCHAR) UNIQUE
  - first_name, last_name, display_name, bio
  - created_at, updated_at

If you already have `user_profile` from your UserManagement module — use it.
If you don't, you can create it with SQL (no Spark):

```sql
CREATE TABLE IF NOT EXISTS `user_profile` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(64) NOT NULL,
  `first_name` VARCHAR(120) NULL,
  `last_name` VARCHAR(120) NULL,
  `display_name` VARCHAR(160) NULL,
  `bio` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_profile_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Session integration
`Libraries/CurrentUser.php` reads current user from session.
Adjust session keys in:
- `Config/Profile.php`

## Using in header block
See Blocks/UserProfile module in this archive.
