<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Events\UserCreated;
use Yuan\AdminCore\Events\UserDeactivated;
use Yuan\AdminCore\Events\UserLevelChanged;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// Users guard rails (spec §6.3): Super Admin accounts are invisible to other
// levels, the Super Admin level is theirs to give, nobody locks themselves out.

uses(RefreshDatabase::class);

function superAdminAccount(): User
{
    $user = User::factory()->create(['name' => 'Admin Utama']);
    $user->assignLevel(Level::superAdmin());

    return $user->refresh();
}

/**
 * @return array<string, mixed>
 */
function accountInput(User $user, Level $level): array
{
    return ['name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'level_id' => $level->id, 'is_active' => true, 'lock_version' => $user->lock_version];
}

it('hides Super Admin accounts from other levels: not listed, every route a 404', function (): void {
    $admin = superAdminAccount();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit', 'users.delete']);

    AssertableInertia::fromTestResponse(get('/admin/users'))
        ->count('users.items', 1)
        ->where('users.items.0.name', fn (string $name): bool => $name !== 'Admin Utama');

    get("/admin/users/{$admin->id}")->assertNotFound();
    get("/admin/users/{$admin->id}/edit")->assertNotFound();
    put("/admin/users/{$admin->id}", accountInput($admin, Level::query()->create(['name' => 'staf', 'label' => 'Staf'])))->assertNotFound();
    post("/admin/users/{$admin->id}/deactivate")->assertNotFound();
    post("/admin/users/{$admin->id}/reset-password", ['mode' => 'generate'])->assertNotFound();
    delete("/admin/users/{$admin->id}")->assertNotFound();
    delete('/admin/users', ['ids' => [$admin->id]])->assertRedirect();

    expect($admin->fresh()?->is_active)->toBeTrue()
        ->and(User::query()->find($admin->id))->not->toBeNull();
});

it('keeps the Super Admin level out of other levels\' hands', function (): void {
    $staff = User::factory()->create();
    $staff->assignLevel(Level::query()->create(['name' => 'staf', 'label' => 'Staf']));
    $staff->refresh();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.create', 'users.edit']);
    $superAdmin = Level::superAdmin();

    AssertableInertia::fromTestResponse(get('/admin/users/create'))
        ->where('options.levels', fn (Collection $levels): bool => $levels->doesntContain('value', $superAdmin->id));
    AssertableInertia::fromTestResponse(get('/admin/users'))
        ->where('table.filters.0.options', fn (Collection $options): bool => $options->doesntContain('value', (string) $superAdmin->id));

    post('/admin/users', ['name' => 'Baru', 'email' => 'baru@contoh.test', 'level_id' => $superAdmin->id, 'is_active' => true, 'password' => 'RahasiaBaru1', 'password_confirmation' => 'RahasiaBaru1'])
        ->assertSessionHasErrors('level_id');
    put("/admin/users/{$staff->id}", accountInput($staff, $superAdmin))->assertSessionHasErrors('level_id');

    expect(User::query()->where('email', 'baru@contoh.test')->exists())->toBeFalse()
        ->and($staff->fresh()?->isSuperAdmin())->toBeFalse();
});

it('lets a Super Admin give the Super Admin level', function (): void {
    $staff = User::factory()->create();
    $staff->assignLevel(Level::query()->create(['name' => 'staf', 'label' => 'Staf']));
    $staff->refresh();
    ModuleAssertions::actingAsSuperAdmin();

    put("/admin/users/{$staff->id}", accountInput($staff, Level::superAdmin()))->assertSessionHasNoErrors();

    expect($staff->fresh()?->isSuperAdmin())->toBeTrue();
});

it('refuses deleting, deactivating, resetting or demoting your own account', function (): void {
    $self = ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit', 'users.delete']);
    assert($self instanceof User);
    $other = Level::query()->create(['name' => 'lain', 'label' => 'Lain']);

    delete("/admin/users/{$self->id}")->assertForbidden();
    delete('/admin/users', ['ids' => [$self->id]])->assertForbidden();
    post("/admin/users/{$self->id}/deactivate")->assertForbidden();
    post("/admin/users/{$self->id}/reset-password", ['mode' => 'generate'])->assertForbidden();
    put("/admin/users/{$self->id}", accountInput($self, $other))->assertSessionHasErrors('level_id');
    put("/admin/users/{$self->id}", [...accountInput($self, $self->level() ?? $other), 'is_active' => false])->assertSessionHasErrors('level_id');

    AssertableInertia::fromTestResponse(get("/admin/users/{$self->id}"))->where('user.is_self', true);
    expect($self->fresh()?->is_active)->toBeTrue()
        ->and($self->fresh()?->level()?->is($other))->toBeFalse();
});

it('holds the own-account rule for a Super Admin too, so the last one stays active', function (): void {
    $self = ModuleAssertions::actingAsSuperAdmin();
    assert($self instanceof User);
    $staff = Level::query()->create(['name' => 'staf', 'label' => 'Staf']);

    post("/admin/users/{$self->id}/deactivate")->assertForbidden();
    post("/admin/users/{$self->id}/reset-password", ['mode' => 'generate'])->assertForbidden();
    delete("/admin/users/{$self->id}")->assertForbidden();
    delete('/admin/users', ['ids' => [$self->id]])->assertForbidden();
    put("/admin/users/{$self->id}", accountInput($self, $staff))->assertSessionHasErrors('level_id');

    expect($self->fresh()?->isSuperAdmin())->toBeTrue()
        ->and($self->fresh()?->is_active)->toBeTrue()
        ->and($self->fresh()?->trashed())->toBeFalse();
});

it('lets only a Super Admin open the trash and restore a user', function (): void {
    $user = User::factory()->create();
    $user->assignLevel(Level::query()->create(['name' => 'staf', 'label' => 'Staf']));
    $user->delete();

    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit', 'users.delete']);
    get('/admin/users/trash')->assertForbidden();
    post('/admin/users/trash/restore', ['ids' => [$user->id]])->assertForbidden();

    ModuleAssertions::actingAsSuperAdmin();
    get('/admin/users/trash')->assertOk();
    post('/admin/users/trash/restore', ['ids' => [$user->id]])->assertRedirect();

    expect(User::query()->find($user->id))->not->toBeNull();
    ModuleAssertions::assertAudited($user, 'restored');
});

it('fires UserCreated, UserLevelChanged and UserDeactivated only after the transaction commits', function (): void {
    Event::fake([UserCreated::class, UserLevelChanged::class, UserDeactivated::class]);
    $level = Level::query()->create(['name' => 'staf', 'label' => 'Staf']);
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.create']);

    post('/admin/users', ['name' => 'Gagal', 'email' => 'gagal@contoh.test', 'level_id' => $level->id, 'is_active' => true, 'password' => 'RahasiaBaru1', 'password_confirmation' => 'beda'])
        ->assertSessionHasErrors('password');
    Event::assertNotDispatched(UserCreated::class);

    post('/admin/users', ['name' => 'Jadi', 'email' => 'jadi@contoh.test', 'level_id' => $level->id, 'is_active' => true, 'password' => 'RahasiaBaru1', 'password_confirmation' => 'RahasiaBaru1'])
        ->assertSessionHasNoErrors();
    Event::assertDispatched(UserCreated::class, fn (UserCreated $event): bool => $event->user->getAttribute('email') === 'jadi@contoh.test');
    Event::assertDispatched(UserLevelChanged::class);

    $user = User::query()->where('email', 'jadi@contoh.test')->sole();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit']);
    post("/admin/users/{$user->id}/activate")->assertRedirect();
    Event::assertNotDispatched(UserDeactivated::class);
    post("/admin/users/{$user->id}/deactivate")->assertRedirect();
    Event::assertDispatchedTimes(UserDeactivated::class, 1);
});
