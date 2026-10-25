---
name: admin-downloadable-list
description: "Use when a module list must be exportable or downloadable as xlsx or csv."
---

# Make a module downloadable

1. Add `download` to `actions` in `module.php`, then `php artisan permissions:sync`; policy `download(Authorizable $user)`.
2. `Exports/{Name}Export.php` extends `ModuleExport`: `baseQuery()` returns `{Name}::query()->visibleTo($this->user)->with(...)`; `headings()` and `row($row)` give the columns (never personal data you wouldn't show in the list). The base class applies the list's current search, filters and sort, and guards against formula injection.
3. Route `Route::get('download', ...)->middleware('permission:{slug}.download')->name('download')` before any `{model}` route; controller: `Gate::authorize('download', {Name}::class)` then `return ExportManager::for({Name}Export::class, $request);`.
4. Page: `<DataTable download={can('{slug}.download') ? download() : undefined}>`. Up to `admin-core.exports.queue_threshold` rows the file arrives at once; above it a queued job writes it and the user finds it under Unduhan. Every download writes exactly one `exported` audit row.
5. Never use the `Excel` facade directly.
6. Tests: the download returns a file and writes the audit row. `composer check`.
