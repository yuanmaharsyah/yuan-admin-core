---
name: admin-add-field
description: "Use when adding a column or field to an existing admin module (new input on the form, new list column, new attribute on the model)."
---

# Add a field to an existing module

1. New migration in `modules/{Name}/Database/Migrations` (a new file; never edit a migration that has shipped). Index it if lists filter or sort on it; a foreign key states its `onDelete`; `down()` drops the column.
2. Model: add it to `$fillable` and `casts()`.
3. Add it to `Store{Name}Data`, `Update{Name}Data` (with rules), `{Name}Data` and, if it's a list column, `{Name}ListItemData`.
4. `php artisan typescript:transform`; TypeScript now shows where the field is missing.
5. Add the input to `resources/js/components/{Name}Form.tsx` (inside `FormField`, the matching control: `TextField`, `NumberField`, `DateField`, `SelectField`, `SwitchField`…) and, optionally, a column in `resources/js/pages/Index.tsx`. Labels in the module's dictionaries (skill `admin-translate`).
6. Add the column to `Exports/{Name}Export.php` if it should be downloadable.
7. Factory: add the field. Tests: add a validation case.
8. Personal data (NIK, NPWP, phone, address, account number) goes into `admin-core.audit.masked_fields` in the same change (skill `admin-audit`).
9. `php artisan migrate`, then `composer check`.

A relation (belongsTo) is skill `admin-relation-field`; a file is skill `admin-attachments`.
