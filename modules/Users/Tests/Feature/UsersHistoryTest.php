<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\get;

// The Riwayat tab on a user (frontend-behavior §8.3a): an optional prop,
// sent only when the tab asks, to viewers with users.view and audit-log.view.

uses(RefreshDatabase::class);

function historyUser(): User
{
    $user = User::factory()->create(['name' => 'Siti Rahmawati']);
    $user->assignLevel(Level::query()->firstOrCreate(['name' => 'staf'], ['label' => 'Staf']));
    $user->update(['name' => 'Siti R.']);

    return $user->refresh();
}

it('sends the history only when the tab asks, with the changed fields', function (): void {
    $user = historyUser();
    ModuleAssertions::actingAsLevelWith(['users.view', 'audit-log.view']);

    AssertableInertia::fromTestResponse(get("/admin/users/{$user->id}"))
        ->missing('history')
        ->reloadOnly('history', fn (AssertableInertia $reload) => $reload
            ->where('history.total', 3)
            ->where('history.next_page', null)
            ->where('history.items.0.event', 'updated')
            ->where('history.items.0.summary', 'Mengubah 1 field')
            ->where('history.items.0.diff.0.new', 'Siti R.')
            ->where('history.items.2.event', 'created'));
});

it('sends no history without audit-log.view', function (): void {
    $user = historyUser();
    ModuleAssertions::actingAsLevelWith(['users.view']);

    AssertableInertia::fromTestResponse(get("/admin/users/{$user->id}"))
        ->where('history', null)
        ->reloadOnly('history', fn (AssertableInertia $reload) => $reload->where('history', null));
});

it('hides Super Admin rows from other viewers and pages by 20', function (): void {
    $user = historyUser();
    $admin = User::factory()->create();
    $admin->assignLevel(Level::superAdmin());
    Audit::actingAs($admin, fn () => Audit::record(AuditEvent::PasswordReset, $user, module: 'users'));

    foreach (range(1, 20) as $i) {
        $user->update(['name' => "Siti {$i}"]);
    }

    ModuleAssertions::actingAsLevelWith(['users.view', 'audit-log.view']);

    AssertableInertia::fromTestResponse(get("/admin/users/{$user->id}"))
        ->reloadOnly('history', fn (AssertableInertia $reload) => $reload
            ->where('history.total', 23)
            ->where('history.next_page', 2)
            ->has('history.items', 20));

    AssertableInertia::fromTestResponse(get("/admin/users/{$user->id}?history_page=2"))
        ->reloadOnly('history', fn (AssertableInertia $reload) => $reload
            ->has('history.items', 3)
            ->where('history.next_page', null)
            ->where('history.items', fn (Collection $items): bool => $items->doesntContain('event', 'password_reset')));
});

it('links a row of a multi-row operation to its batch in the Log Aktivitas', function (): void {
    $user = historyUser();
    Audit::batch(function () use ($user): void {
        $user->update(['name' => 'Satu']);
        $user->update(['email' => 'satu@contoh.test']);
    });
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get("/admin/users/{$user->id}"))
        ->reloadOnly('history', fn (AssertableInertia $reload) => $reload
            ->where('history.items.0.batch_size', 2)
            ->where('history.items.0.batch_href', fn (string $href): bool => str_contains($href, 'filter%5Bbatch%5D=') && str_contains($href, 'range=custom')));
});
