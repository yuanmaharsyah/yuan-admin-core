## yuan/admin-core

This project is a Laravel 13 + Inertia 3 + React 19 + TypeScript admin panel built on the `yuan/admin-core` package. Every feature is a **module** in `modules/{Name}` with a `module.php`; routes, menu, command palette and permissions come from that file. Each user has exactly one **Level**; a level holds permissions `{slug}.{action}`. Every change is written to the audit log automatically. The `admin-*` skills hold the step-by-step recipes: use them.

### Workflow

- Before coding, give a short plan in chat: files, steps, tests, open questions. Wait for approval on anything larger than a small fix.
- Prefer the generator: `php artisan make:module` and `php artisan make:widget` write the canonical code; adjust what they wrote instead of writing modules by hand.
- Run `composer check` until it is green before saying a task is done, and report its summary, the decisions you made where nothing was specified, and anything deferred.
- If something is unspecified, choose the simplest option and say so. Never invent requirements. If instructions conflict, stop and ask.

### General

- Never add, remove or upgrade a dependency without approval. Never edit `vendor/` (customize a core screen through the `admin-customize-core` skill), `node_modules/`, `resources/js/types/generated.d.ts` or the Wayfinder output (`resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder`): regenerate them with `php artisan typescript:transform` and `php artisan wayfinder:generate --with-form`, and commit the result.
- Never weaken or skip a test, a Larastan rule or a lint rule to get green. Fix the cause.
- No placeholders, `TODO`s, commented-out code or dead code in finished work.
- Commits: scoped, `type(scope): message` (`feat(invoices)`, `fix(users)`).

### Module contract

- A module has the shape `make:module` writes. `module.php` has every key: `name`, `slug`, `label`, `icon` (a lucide name), `group` (a key of `admin-core.menu.groups`), `order`, `enabled`, `core` (false in a project), `superadmin_only`, `menu`, `actions` (always including `view`) and `widgets`. Slugs are unique.
- Routes in a module are relative; the registry adds the `/admin/{slug}` prefix, the `{slug}.` name prefix and the base middleware.
- A module may use another module's **Models** only. Shared logic belongs in the core package.
- Inertia page names are `{Name}::{Page}`.

### RBAC

- One Level per user via `assignLevel()`. Never `assignRole`, `givePermissionTo` or `syncPermissions` on a user.
- Permissions are created only by `php artisan permissions:sync`, never in migrations, seeders, controllers or tests (tests use `ModuleAssertions::actingAsLevelWith()`).
- Every admin route has the `permission:{slug}.{action}` middleware (or `superadmin`), and the controller calls `Gate::authorize()`. The frontend `can()` is cosmetic.
- Levels, the permission matrix, Settings and Trash are Super Admin only and never grantable.

### Backend

- `declare(strict_types=1);`, typed parameters and returns, Larastan level 8 clean.
- All Inertia props and all request inputs are laravel-data objects with `#[TypeScript]`. No FormRequests, no raw arrays, no models passed to Inertia. Validation goes in the input Data `rules()`; authorization in policies.
- Controllers are thin: authorize, call the model or service, render or redirect. No `DB` facade in controllers. Multi-step writes go in `DB::transaction()`.
- Lists: `{Name}::query()->visibleTo($user)->dataTable($request)` wrapped in `PaginatedData::fromPaginator()`.
- Updates use `PreventsStaleUpdates` (`lock_version` column, carried by the update Data, `updateUnlessStale()`).
- Downloads only through `ExportManager`. Files only through `HasAttachments` (private disk, signed URLs). Nothing uses the public disk.
- Models: `$fillable` (never `$guarded = []`), casts declared, `SoftDeletes`, `LogsModuleActivity` with a meaningful `auditLabel()`.
- Migrations: never edit one that has shipped; every foreign key states its `onDelete`; every filter or sort column has an index; every migration has a working `down()`.
- Jobs have `#[Tries]`, `#[Backoff]` and `#[Timeout]`; a job acting for a user passes that user so audit rows have a causer.
- Audit: model changes are logged by the trait. Every other event uses `Audit::record()` with an `AuditEvent` case or a declared custom action; multi-record operations go in `Audit::batch()`. Never call `activity()`, never queue audit writes, never update or delete `activity_log` rows. A new personal-data field (NIK, NPWP, phone, address, account number) goes into `admin-core.audit.masked_fields` in the same change.
- Widgets extend `Widget`, are listed in `module.php` under `widgets`, use `visibleTo($user)` and keep `sharedCache()` false unless the data is identical for every user of a level.
- No `env()` outside config, no `dd` / `dump` / `ray`, no raw SQL with interpolated input.

### Frontend

- TS strict, no `any`, no `@ts-ignore`. Types come from `generated.d.ts`; never hand-write prop types that a Data class can generate.
- URLs and route names only through Wayfinder helpers. Never hardcode a URL string.
- Use the `@admin-core` components (`DataTable`, `FormField`, `RelationSelect`, `ConfirmDialog`…) and the shadcn primitives in `@admin-core/components/ui`. A new generic component belongs in the core package.
- Forms: `useAdminForm` + `FormField`, `UnsavedChangesGuard` on edit forms, `StaleUpdateDialog` for a 409.
- Formatting only through `@admin-core/lib/format` (id-ID). User strings through `useT()`, Bahasa Indonesia first; every key in both the module's `id.ts` and `en.ts`.
- Theme tokens only (`bg-card`, `text-muted-foreground`…): no hex colors, no inline styles. Works in light and dark mode, at 375px and with the keyboard only.
- No business logic in TSX, routes files or Blade.

### Testing

Each module's tests (in `modules/{Name}/Tests`, generated by `make:module`) cover: guest redirected to login; 403 without the permission for every action; success with it; validation of required fields; download returns a file and writes an audit row; delete is soft and only Super Admin restores; create, update and delete write their audit events with masked fields masked (`ModuleAssertions::assertAudited()`); a stale update returns 409; `visibleTo()` is applied (`ModuleAssertions::assertScopedToUser()`); each widget is hidden without its permission and stays within its time budget. Use factories and `RefreshDatabase`; never hardcode IDs.

### Forbidden

- Hardcoded menus, permissions, level names or URLs.
- Disabling CSRF protection, policies or mass-assignment protection to make something work.
- Public-disk uploads, unsigned file URLs, downloads that bypass `ExportManager`.
- Logging passwords, tokens or unmasked personal data; editing or deleting audit rows.
- Committing `.env`, `auth.json`, credentials or real personal data in seeders and fixtures.
