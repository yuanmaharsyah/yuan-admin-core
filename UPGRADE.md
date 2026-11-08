# Upgrade Guide

## Access

A project from `composer create-project yuanmaharsyah/admin-skeleton` gets the package from Packagist over HTTPS: nothing to set up. A project that uses a private fork over SSH: [README.md → Private forks over SSH](README.md#private-forks-over-ssh) (developer keys, server deploy keys). Never commit `auth.json`.

## Standard steps (every upgrade)

Run in the project root:

1. `composer update yuanmaharsyah/admin-core`
2. Regenerate types and routes: `php artisan typescript:transform && php artisan wayfinder:generate --with-form` (the skeleton's `post-update-cmd` already runs both, plus `php artisan boost:update --no-discover` for the AI guideline and skills), then commit the regenerated `resources/js/types/generated.d.ts`, `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder`, `CLAUDE.md` and `.claude/skills`.
3. `npm run typecheck`: fix any TS error caused by a changed core Data class.
4. `php artisan migrate && php artisan permissions:sync && php artisan optimize`
5. `composer check`

Version-specific steps are listed below, newest first.

## 2.0.0

The admin shell and theme change (restyle 01, see CHANGELOG). No PHP or Data changes.

1. A project that imported `AppRail` or `ItemPanel`, or used the `bg-rail`, `text-rail-foreground`, `bg-sidebar-active`, `text-sidebar-active-foreground`, `w-rail` or `w-panel` utilities: use `AppSidebar` and `bg-sidebar-primary` / `bg-sidebar-accent` instead (`npm run typecheck` and `npm run build` name every place).
2. A project page that showed breadcrumbs itself: `PageHeader` now renders them.
3. Branding still overrides `--highlight` only; it now colours chart series 1, not the active menu item.
4. Restyle 03 lives partly in the skeleton, which `composer update` doesn't touch. Copy from the matching `yuanmaharsyah/admin-skeleton` release:
   - `database/migrations/2026_11_08_000001_add_two_factor_columns_to_users_table.php`, then `php artisan migrate`;
   - `app/Models/User.php` (`TwoFactorAuthenticatable`, hidden secrets), `app/Actions/Fortify/CreateNewUser.php`, `app/Http/Responses/RegisterResponse.php`;
   - the `features` of `config/fortify.php` (`registration()`, `twoFactorAuthentication([...])`) and the new views, responses and audit listeners of `app/Providers/FortifyServiceProvider.php`;
   - `public/errors/502.html`, plus `error_page 502 504 /errors/502.html;` in the Nginx server block.
   Registration stays closed until Super Admin turns on Pengaturan → Keamanan → "Izinkan pendaftaran akun".
5. Add `Yuan\AdminCore\Http\Middleware\RequireTwoFactor` to the `web` group in `bootstrap/app.php`, right after `RequirePasswordChange` (it does nothing until Pengaturan → Keamanan → "Wajibkan verifikasi dua langkah" is on).
6. A page override or test that rendered `Core::Account/Profile` now gets `Core::Account/Settings`, with the old `profile` prop under `account.profile`. A normal password change from Akun returns to Akun (the forced change still goes home).

## 1.0.0

First release. Nothing to do.
