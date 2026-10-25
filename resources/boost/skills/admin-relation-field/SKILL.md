---
name: admin-relation-field
description: "Use when a module record must point to a record of another module (belongsTo, a foreign key chosen in a searchable select)."
---

# Add a relation field (belongsTo)

With the generator: `--fields="kecamatan_id:foreign(kecamatans)"`. The target module needs a `lookup` route.

By hand:

1. Migration: `$table->foreignId('kecamatan_id')->constrained()->restrictOnDelete();` (always state `onDelete`), in a new migration.
2. Model: a `belongsTo`; add the column to `$fillable`.
3. The target module has `GET lookup` with `permission:{slug}.view` that returns `LookupData` (at most 20 rows, through `visibleTo()`).
4. Input Data: the id is an `int` validated with `new VisibleRecord({Target}::class)` (never `exists:`), so users can only link records they may see.
5. Eager-load the relation in `index()`; `{Name}ListItemData` exposes `kecamatan_name`, never the model.
6. Form: `<RelationSelect lookupRoute={lookup} valueLabel={...}>` from `@admin-core/components/admin/RelationSelect`.
7. Tests: an id the user can't see fails validation.
8. `php artisan typescript:transform`, `php artisan wayfinder:generate --with-form`, `composer check`.
