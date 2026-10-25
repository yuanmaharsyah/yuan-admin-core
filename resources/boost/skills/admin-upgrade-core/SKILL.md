---
name: admin-upgrade-core
description: "Use when updating the yuan/admin-core package in this project to a newer version."
---

# Upgrade yuan/admin-core

1. Read `vendor/yuan/admin-core/CHANGELOG.md` and the version sections of `vendor/yuan/admin-core/UPGRADE.md` for every version between the current and the target one.
2. `composer update yuan/admin-core` (over SSH; access is described in `vendor/yuan/admin-core/README.md`; never commit `auth.json`). `post-update-cmd` regenerates the types, the Wayfinder routes and the AI guidelines and skills (`php artisan boost:update`).
3. Commit the regenerated `resources/js/types/generated.d.ts`, `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder` and the Boost files.
4. `npm run typecheck`: fix any error caused by a changed core Data class.
5. `php artisan migrate && php artisan permissions:sync && php artisan optimize`
6. Apply the version-specific steps from `UPGRADE.md`, then `composer check`.
