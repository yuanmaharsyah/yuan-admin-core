---
name: admin-add-setting
description: "Use when the project needs a new configurable value that a Super Admin edits in Pengaturan (Settings), such as contact data or a project option."
---

# Add a setting

1. In `config/admin-core.php` (if the project has none yet: `php artisan vendor:publish --tag=admin-core-config`) → `settings`, add a field to a tab (`'kontak' => ['fields' => ['fax' => [...]]]`) or a new tab (`'proyek' => ['label' => 'Proyek', 'fields' => [...]]`). A field has `type` (string, text, integer, boolean, enum with `options`, phone, whatsapp, postal_code, social), `label`, optional `help`, `rules`, `default`, `public` (shared with every page as `app.settings['tab.field']`). `image` is only for the three branding fields.
   The project's file is merged deeply over the package defaults: list only what you add or change; the default tabs and fields stay.
2. Read it with `settings('tab.field')` (the stored value, else `default`); cached and refreshed on save.
3. Nothing else: the Settings page renders, validates and audits it.
4. A personal-data field goes into `admin-core.audit.masked_fields` (skill `admin-audit`).
5. `composer check`.
