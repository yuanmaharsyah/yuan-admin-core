---
name: admin-customize-core
description: "Use when a core screen or behaviour (Users, Levels, Beranda, Log Aktivitas, auth pages) must look or behave differently in this project without editing vendor."
---

# Customize a core screen in this project

Never edit `vendor/yuan/admin-core`. In order of preference:

- **UI only:** add `'Users::Index' => 'App::Users/Index'` to `admin-core.pages.overrides` in `config/admin-core.php` (if the project has none yet: `php artisan vendor:publish --tag=admin-core-config`) and create `resources/js/pages/Users/Index.tsx` (copy it from `vendor/yuan/admin-core/modules/Users/resources/js/pages/Index.tsx`, then edit; the case must match exactly).
- **Extra user fields:** a project migration for the columns (index the ones you filter on); a class implementing `Yuan\AdminCore\Users\UserFormExtension` (`fields()`: `ExtraFieldData` of type `text`, `number` or `select`; `rules($user)`: rules per field name); set `admin-core.users.form_extension` to it. The Users forms, validation (`extra.{name}`), the detail page and `$fillable` pick them up. Personal data (NIP, NIK…) also goes into `admin-core.audit.masked_fields`. Example: `tests/Fixtures/EmployeeFields.php`.
- **Extra user behaviour:** `App\Models\User` extends the core User (`admin-core.models.user`); add relations or `applyVisibility()` there.
- **Side effects:** listen to the core events in `Yuan\AdminCore\Events` (`UserCreated`, `UserLevelChanged`, `UserDeactivated`, `LevelPermissionsUpdated`, `ExportCompleted`).
- **Anything else:** stop and ask the owner; a change to the core belongs in the package.

Then `composer check`.
