# Upgrade Guide

## Standard steps (every upgrade)

Run in the project root:

1. `composer update yuan/admin-core`
2. Regenerate types and routes: `php artisan typescript:transform && php artisan wayfinder:generate --with-form` (the skeleton's `post-update-cmd` already runs both), then commit the regenerated `resources/js/types/generated.d.ts`, `resources/js/actions`, `resources/js/routes` and `resources/js/wayfinder`.
3. `npm run typecheck`: fix any TS error caused by a changed core Data class.
4. `php artisan migrate && php artisan permissions:sync && php artisan optimize`
5. `composer check`

Version-specific steps are listed below, newest first.
