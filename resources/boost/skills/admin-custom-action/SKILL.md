---
name: admin-custom-action
description: "Use when a module needs an action beyond view/create/edit/delete/download, such as approve, verify, publish or cancel, with its own permission."
---

# Add a custom action (e.g. `approve`)

1. `module.php` → `'actions' => [..., 'approve']` (one lowercase word).
2. `php artisan permissions:sync` (the Hak Akses matrix now shows the column).
3. Policy: `public function approve(User $user, {Name} $record): bool`.
4. Route in `modules/{Name}/Routes`: `Route::post('{record}/approve', [{Name}Controller::class, 'approve'])->middleware('permission:{slug}.approve')->name('approve');`
5. Controller: load the record through `{Name}::query()->visibleTo($request->user())`, `Gate::authorize('approve', $record)`, do the work in `DB::transaction()`, then `Audit::record('approve', $record, ['note' => $data->note])` and redirect with flash. Input is a Data object.
6. UI: a `<Can permission="{slug}.approve">` button calling the Wayfinder action, with `useAction()` for the pending state.
7. Tests: allowed and denied cases (`ModuleAssertions::assertForbiddenWithout()`), and `ModuleAssertions::assertAudited($record, 'approve')`.
8. `composer check`.
