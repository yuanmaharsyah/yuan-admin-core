<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Events\LevelPermissionsUpdated;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// Levels and the permission matrix (frontend-behavior §8.2): Super Admin only,
// never grantable; the protected level can't be changed (spec §6.3).

uses(RefreshDatabase::class);

function operatorLevel(): Level
{
    return Level::query()->create(['name' => 'operator-uji', 'label' => 'Operator Uji', 'description' => 'Input data']);
}

it('redirects guests to login', function (): void {
    $level = operatorLevel();

    foreach ([['get', '/admin/levels'], ['get', '/admin/levels/create'], ['post', '/admin/levels'], ['get', "/admin/levels/{$level->id}/edit"], ['put', "/admin/levels/{$level->id}"], ['delete', "/admin/levels/{$level->id}"], ['get', "/admin/levels/{$level->id}/permissions"], ['put', "/admin/levels/{$level->id}/permissions"]] as [$method, $uri]) {
        ModuleAssertions::assertGuestRedirected($method, $uri);
    }
});

it('refuses every other level, even one holding every permission there is', function (): void {
    $level = operatorLevel();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.create', 'users.edit', 'users.delete', 'users.download']);

    get('/admin/levels')->assertForbidden();
    get('/admin/levels/create')->assertForbidden();
    post('/admin/levels', ['label' => 'Baru'])->assertForbidden();
    get("/admin/levels/{$level->id}/edit")->assertForbidden();
    put("/admin/levels/{$level->id}", ['label' => 'Ganti', 'lock_version' => 0])->assertForbidden();
    delete("/admin/levels/{$level->id}")->assertForbidden();
    get("/admin/levels/{$level->id}/permissions")->assertForbidden();
    put("/admin/levels/{$level->id}/permissions", ['grants' => ['users' => ['view']]])->assertForbidden();

    expect($level->fresh()?->label)->toBe('Operator Uji')
        ->and($level->permissions()->count())->toBe(0);
});

it('lists the levels with their user counts, Super Admin first', function (): void {
    $level = operatorLevel();
    User::factory()->count(2)->create()->each->assignLevel($level);
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get('/admin/levels')->assertOk())
        ->component('Levels::Index')
        ->where('levels.items.0.is_protected', true)
        ->where('levels.items.1.label', 'Operator Uji')
        ->where('levels.items.1.users_count', 2);
});

it('creates a level with a unique name, audited, then opens its matrix', function (): void {
    ModuleAssertions::actingAsSuperAdmin();

    post('/admin/levels', [])->assertSessionHasErrors('label');
    post('/admin/levels', ['label' => 'Bendahara', 'description' => 'Keuangan'])->assertRedirect();

    $level = Level::query()->where('label', 'Bendahara')->sole();
    expect($level->name)->toBe('bendahara');
    ModuleAssertions::assertAudited($level, 'created');

    post('/admin/levels', ['label' => 'Bendahara'])->assertSessionHasErrors('label');
});

it('updates a level, audited, and gives a stale save a 409', function (): void {
    $level = operatorLevel();
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get("/admin/levels/{$level->id}/edit")->assertOk())->component('Levels::Edit');
    put("/admin/levels/{$level->id}", ['label' => 'Operator Data', 'description' => null, 'lock_version' => 0])->assertSessionHasNoErrors()->assertRedirect();
    put("/admin/levels/{$level->id}", ['label' => 'Lain', 'description' => null, 'lock_version' => 0])->assertStatus(409);

    expect($level->fresh()?->label)->toBe('Operator Data');
    ModuleAssertions::assertAudited($level, 'updated');
});

it('never lets the Super Admin level be edited, deleted or given a matrix', function (): void {
    $superAdmin = Level::superAdmin();
    ModuleAssertions::actingAsSuperAdmin();

    get("/admin/levels/{$superAdmin->id}/edit")->assertForbidden();
    put("/admin/levels/{$superAdmin->id}", ['label' => 'Raja', 'lock_version' => $superAdmin->lock_version])->assertForbidden();
    delete("/admin/levels/{$superAdmin->id}")->assertForbidden();
    get("/admin/levels/{$superAdmin->id}/permissions")->assertForbidden();
    put("/admin/levels/{$superAdmin->id}/permissions", ['grants' => []])->assertForbidden();

    expect($superAdmin->fresh()?->label)->toBe('Super Admin');
});

it('refuses deleting a level that still has users, and deletes an empty one, audited', function (): void {
    $used = operatorLevel();
    User::factory()->create()->assignLevel($used);
    $empty = Level::query()->create(['name' => 'kosong', 'label' => 'Kosong']);
    ModuleAssertions::actingAsSuperAdmin();

    delete("/admin/levels/{$used->id}")->assertRedirect()->assertInertiaFlash('error', 'Level masih dipakai user. Pindahkan user-nya ke level lain dulu.');
    expect(Level::query()->find($used->id))->not->toBeNull();

    delete("/admin/levels/{$empty->id}")->assertRedirect('/admin/levels');
    expect(Level::query()->find($empty->id))->toBeNull();
    ModuleAssertions::assertAudited($empty, 'deleted');
});

it('shows the matrix without the Super Admin-only modules', function (): void {
    $level = operatorLevel();
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get("/admin/levels/{$level->id}/permissions")->assertOk())
        ->component('Levels::Permissions')
        ->where('matrix.level.id', $level->id)
        ->where('matrix.rows', fn (Collection $rows): bool => $rows->contains('slug', 'users') && $rows->doesntContain('slug', 'levels'));
});

it('saves the matrix (view implied, audited, event fired) and applies it on the next request', function (): void {
    Event::fake([LevelPermissionsUpdated::class]);
    artisan('permissions:sync');
    $level = operatorLevel();
    $member = User::factory()->create();
    $member->assignLevel($level);
    ModuleAssertions::actingAsSuperAdmin();

    put("/admin/levels/{$level->id}/permissions", ['grants' => ['users' => ['edit']]])->assertSessionHasNoErrors()->assertRedirect();

    expect($level->permissions()->pluck('name')->sort()->values()->all())->toBe(['users.edit', 'users.view']);
    ModuleAssertions::assertAudited($level, 'permissions_updated');
    Event::assertDispatched(LevelPermissionsUpdated::class);

    actingAs($member->refresh());
    get('/admin/users')->assertOk();
    get('/admin/users/create')->assertForbidden();
});

it('rejects an unknown module or an undeclared action without saving', function (): void {
    $level = operatorLevel();
    ModuleAssertions::actingAsSuperAdmin();

    put("/admin/levels/{$level->id}/permissions", ['grants' => ['levels' => ['view']]])->assertSessionHasErrors('grants');
    put("/admin/levels/{$level->id}/permissions", ['grants' => ['users' => ['approve']]])->assertSessionHasErrors('grants');

    expect($level->permissions()->count())->toBe(0);
});
