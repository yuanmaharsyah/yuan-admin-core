# Changelog

All notable changes to `yuan/admin-core` are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses [SemVer](https://semver.org/).

## [Unreleased]

### Added
- Package scaffold: Testbench test suite on in-memory SQLite, Pest 5 with arch hygiene rules, Larastan level 8 (no baseline) and Pint with strict types.
- Package auto-discovery (`AdminCoreServiceProvider`) and `config/admin-core.php`.
- Module registry (`Yuan\AdminCore\Registry`): discovers the core and project module roots, validates every `module.php` (spec §3.1), fails boot on duplicate slugs or names, registers module providers, migrations and routes (`/admin/{slug}`, `{slug}.`, configured base middleware; `superadmin_only` routes require `admin-core.superadmin`).
- `admin-core.modules.disabled` turns off non-core modules of the core root.
- `modules:cache` / `modules:clear` (hooked into `optimize` / `optimize:clear`) and `modules:list`.
- `PageResolver`: `{Name}::{Page}` from `resources/js/pages` (case-exact), `App::` pages, `admin-core.pages.overrides`, and `viteEntry()` for `@vite` preloading; `resolvePage()` for the client.
- `MenuBuilder`: grouped, ordered, permission-filtered menu (`MenuGroupData` / `MenuItemData`).
- `admin:doctor`: npm dependencies against `admin-core.npm.json`, module cache state, environment sanity.
- Dependencies: `laravel/framework ^13.0`, `inertiajs/inertia-laravel ^3.0`, `composer/semver ^3.4`, `spatie/laravel-data ^4.23`, `spatie/laravel-typescript-transformer ^3.3`.
- Shared Data objects (`#[TypeScript]`): `AuthData`, `UserData`, `MenuGroupData`, `MenuItemData`, `AppData`, `FlashData` (native Inertia flash), `PaginatedData<T>` with `PaginationMetaData` / `PaginationLinksData`; `PaginatedData::fromPaginator()` keeps the query string.
- `Http\Middleware\HandleInertiaRequests`: shares `auth`, `menu`, `app` as Data objects; projects extend it to add props.
- `UPGRADE.md` with the standard upgrade steps.
- Audit core (`Yuan\AdminCore\Audit`, spec §7.1): `AuditEvent` taxonomy (labels, badges, fixed log names); `Audit::record()`, `Audit::batch()` (shared `batch_uuid`, nested batches join the outer one) and `Audit::actingAs()` (explicit causer for jobs); `LogsModuleActivity` model trait with `auditLabel()`; `AuditContext` (web / cli / queue); `Masker` with `admin-core.audit.masked_fields` (omit / partial / full) applied to `attribute_changes` and `properties`; append-only `ImmutableActivity` (`ImmutableAuditException`); auth listeners for `login`, `logout`, `login_failed`, `login_locked`, `password_reset`.
- Core migration `create_activity_log_table` (spatie v5 columns + `batch_uuid` + indexes); projects don't publish the spatie stub.
- `ModuleRegistry::forClass()` and `ModuleDefinition::customActions()` / `STANDARD_ACTIONS`.
- Dependency: `spatie/laravel-activitylog ^5.1`. The package forces its activitylog settings (activity model, write action, no buffering, default log name `system`).
- RBAC core (`Yuan\AdminCore\Permissions`, spec §6): `Level` (spatie Role with `label`, `description`, `is_protected`; `Level::superAdmin()`), `HasLevel` user trait (`assignLevel()` with a batched `level_changed`, `level()`, `isSuperAdmin()`; `assignRole` / `removeRole` / `syncRoles` / `givePermissionTo` / `syncPermissions` throw), `PermissionSynchronizer` + `permissions:sync`, `PermissionMatrix` (build, save with the dependency rule, one batched `permissions_updated`, `LevelPermissionsUpdated` event), `GuardRailViolation`.
- Guard rails: always ≥1 active Super Admin; the protected level is immutable; a level with users can't be deleted (checked before spatie detaches anything).
- Session revocation on deactivate, level change and password change through `users.session_version` (raised atomically; a session is valid only while the version it stored at login matches, on any session driver) plus a rotated remember token; stored `sessions` rows are deleted on the database driver. Core migration `replace_sessions_invalidated_at_with_session_version` replaces the earlier timestamp column, which let a session signed in during the same second as a revocation survive it.
- Middleware aliases `active` (`EnsureUserIsActive`; projects also append it to the `web` group, guests and `logout` pass through), `superadmin` (`EnsureSuperAdmin`) and `permission` (spatie); `Gate::before` passes Super Admin.
- `Auth\AuthenticateUser` for `Fortify::authenticateUsing()`: inactive accounts are refused at login with `login_failed` (reason `inactive`).
- `ImmutableActivity::visibleTo($viewer)` (SA visibility rule) and the `HasActivityHistory` model trait.
- `AuthData` gains `level` (`LevelData`) and `permissions` (Super Admin: every synced permission); `PermissionMatrixData` / `PermissionMatrixRowData`.
- `AdminCoreSeeder` (idempotent; `admin-core.seed.password` / `ADMIN_SEED_PASSWORD`, required in production) and `admin:create-superadmin`.
- Core migrations `create_permission_tables` and `add_admin_columns_to_users_table` (`username`, `is_active`, `last_login_at`, soft deletes). Login stamps `last_login_at`.
- Failed and locked logins resolve the account from the identifier, so the SA visibility rule can hide attempts on Super Admin accounts.
- Dependency: `spatie/laravel-permission ^8.3`.
- `Testing\ContractAssertions`: the runtime contract checks of spec §9.1, run over the current app's modules (projects call them from `tests/Feature/ContractTest.php`).
- `Testing\ModuleAssertions`: module test helpers `actingAsLevelWith()`, `actingAsSuperAdmin()`, `assertGuestRedirected()`, `assertForbiddenWithout()`, `assertAudited()` (with masked-field checks) and `assertNotVisibleToNonSuperAdmin()`.
- `Audit\NotAudited` attribute: the opt-out from `LogsModuleActivity`, with a required reason.

### Changed
- Base admin route middleware is now `web, auth, active`; superadmin_only modules use the `superadmin` middleware instead of `can:admin-core.superadmin`.

### Fixed
- `LogsModuleActivity` no longer stores attributes with an `encrypted` cast; the trait read them decrypted, so they reached `attribute_changes` in plain text.
