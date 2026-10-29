---
name: admin-make-module
description: "Use when creating a new admin module (a new feature, CRUD screen, master data or list of records) in a yuanmaharsyah/admin-core project. Runs php artisan make:module and finishes what it wrote."
---

# Create a module

Never write a module by hand: `make:module` writes the whole contract (model, policy, Data in / out, controller, routes, migration, factory, seeder, export, pages, form, dictionaries and tests) in the canonical style.

## 1. Generate

```bash
php artisan make:module Kendaraan \
  --fields="plat_nomor:string,merk:string,tahun:integer,tanggal_pajak:date?,status:enum(aktif,dijual),catatan:text?,scan_stnk:file(single,required,pdf|jpg,2048)" \
  --actions=view,create,edit,delete,download \
  --group=master --icon=car
```

- `Name` is singular PascalCase; the slug defaults to its plural kebab form (`--slug`, `--label` override).
- Field types: `string, text, integer, decimal, currency, boolean, date, datetime, enum(a,b), foreign(table), file(...)`; a trailing `?` makes a field nullable. Name fields in Indonesian snake_case: labels come from the names (`tanggal_mulai` → "Tanggal mulai"). The first `string` field is the record's label (list, title, audit label).
- `file(single|multiple, required, pdf|jpg, 2048)`: an attachment slot (skill `admin-attachments`), options in any order.
- `foreign(table)` needs a module whose model uses that table and has a `lookup` route (skill `admin-relation-field`); otherwise the generator stops and says so.
- `--actions`: only the five standard actions; custom ones afterwards (skill `admin-custom-action`). `--superadmin-only`: no permissions, only Super Admin.
- `--group` is a key of `admin-core.menu.groups` (see `config/admin-core.php` (if the project has none yet: `php artisan vendor:publish --tag=admin-core-config`)); `--icon` a lucide icon name.
- Bad input writes nothing. An existing folder is refused; `--force` regenerates only a folder `make:module` wrote with no uncommitted changes.

It then runs Pint and Prettier, `php artisan permissions:sync`, `php artisan typescript:transform` and `php artisan wayfinder:generate --with-form`. It never migrates. A field that looks like personal data and isn't in `admin-core.audit.masked_fields` gets a warning: add it (skill `admin-audit`).

## 2. Finish

1. Review the migration in `modules/{Name}/Database/Migrations` (indexes on filter / sort columns, `onDelete` on foreign keys), then `php artisan migrate`.
2. Adjust validation in `Data/Store{Name}Data.php` and `Data/Update{Name}Data.php` (`rules()`, Indonesian names in `attributes()`).
3. Adjust the list columns in `resources/js/pages/Index.tsx` and, if needed, `dataTableSearch()` / `dataTableSorts()` / `dataTableFilters()` on the model (index every sort and filter column).
4. Check that `auditLabel()` on the model returns something a person recognises (a number or name).
5. Strings: the module's `resources/js/lang/id.ts` and `en.ts` (skill `admin-translate`).
6. Optionally add a Beranda widget (skill `admin-dashboard-widget`).
7. `composer check` green. Then a Super Admin grants access: Levels → Hak Akses.

## What the generator wrote (for later changes)

- List: the model `implements DataTableModel` with `HasDataTable` and `HasVisibilityScope`; `index()` returns `PaginatedData::fromPaginator()` of `{Name}::query()->visibleTo($request->user())->dataTable($request)` plus `'table' => {Name}::dataTableState($request)`; the page renders `<DataTable>`.
- An "own unit only" rule goes in `applyVisibility()` on the model.
- Delete: `Trash::delete()` (one transaction, one audit batch, undo for Super Admin); `TrashController::routes()` before any `{model}` route.
- Create / edit / show: `useAdminForm`, `<FormFooter>`, `<UnsavedChangesGuard>`, `<StaleUpdateDialog>` on edit; `updateUnlessStale()` with `lock_version`; the Show page has a Riwayat tab from `ActivityHistory::prop()`.
