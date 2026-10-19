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
