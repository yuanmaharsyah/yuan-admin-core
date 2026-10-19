<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Permissions\GuardRailViolation;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

uses(RefreshDatabase::class);

beforeEach(fn () => syncPermissions());

it('replaces the previous level and writes one batched level_changed', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith([], 'kasir'));
    $user->assignLevel($admin = levelWith([], 'admin'));

    $row = latestActivity();
    expect($user->level()?->is($admin))->toBeTrue()
        ->and(DB::table('model_has_roles')->where('model_id', $user->id)->count())->toBe(1)
        ->and($row->only(['event', 'log_name', 'subject_id']))->toBe(['event' => 'level_changed', 'log_name' => 'users', 'subject_id' => $user->id])
        ->and($row->getProperty('meta'))->toMatchArray(['from' => 'kasir', 'to' => 'admin'])
        ->and($row->batch_uuid)->toBeString();
});

it('does nothing when the level is the same', function (): void {
    $user = makeUser();
    $user->assignLevel($level = levelWith([]));
    $user->assignLevel($level);

    expect(ImmutableActivity::query()->where('event', 'level_changed')->count())->toBe(1);
});

it('refuses direct roles and permissions on a user', function (Closure $call): void {
    $user = makeUser();
    $user->assignLevel(levelWith([]));

    expect(fn () => $call($user))->toThrow(GuardRailViolation::class);
})->with([
    'assignRole' => [fn (User $user) => $user->assignRole('staf')],
    'removeRole' => [fn (User $user) => $user->removeRole('staf')],
    'syncRoles' => [fn (User $user) => $user->syncRoles(['staf'])],
    'givePermissionTo' => [fn (User $user) => $user->givePermissionTo('demo.view')],
    'syncPermissions' => [fn (User $user) => $user->syncPermissions(['demo.view'])],
]);

it('gives permissions only through the level', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith(['demo.view']));

    expect($user->can('demo.view'))->toBeTrue()
        ->and($user->can('demo.create'))->toBeFalse()
        ->and(DB::table('model_has_permissions')->count())->toBe(0);
});

it('knows Super Admin by the protected level, and lets it pass every gate', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith(['demo.view']));
    $superAdmin = superAdminUser();

    expect($user->isSuperAdmin())->toBeFalse()
        ->and($superAdmin->isSuperAdmin())->toBeTrue()
        ->and($superAdmin->level()?->is(Level::superAdmin()))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('demo.create'))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('any-ability-at-all'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('admin-core.superadmin'))->toBeFalse();
});
