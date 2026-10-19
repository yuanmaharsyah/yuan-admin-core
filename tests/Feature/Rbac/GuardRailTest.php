<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Permissions\GuardRailViolation;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

uses(RefreshDatabase::class);

it('keeps the last active Super Admin', function (Closure $change): void {
    $superAdmin = superAdminUser();

    expect(fn () => $change($superAdmin))->toThrow(GuardRailViolation::class, 'at least one active Super Admin')
        ->and(DB::table('model_has_roles')->where('model_id', $superAdmin->id)->count())->toBe(1)
        ->and($superAdmin->fresh()?->isSuperAdmin())->toBeTrue()
        ->and($superAdmin->fresh()?->is_active)->toBeTrue();
})->with([
    'deactivate' => [fn (User $user) => $user->update(['is_active' => false])],
    'soft delete' => [fn (User $user) => $user->delete()],
    'force delete' => [fn (User $user) => $user->forceDelete()],
    'level change' => [fn (User $user) => $user->assignLevel(levelWith([]))],
]);

it('allows it once another active Super Admin exists', function (): void {
    $first = superAdminUser('Root');
    superAdminUser('Admin');

    $first->update(['is_active' => false]);

    expect($first->fresh()?->is_active)->toBeFalse();
});

it('does not count an inactive Super Admin', function (): void {
    $first = superAdminUser('Root');
    superAdminUser('Admin')->update(['is_active' => false]);

    expect(fn () => $first->update(['is_active' => false]))->toThrow(GuardRailViolation::class);
});

it('keeps the protected level immutable, its users and permissions included', function (Closure $change): void {
    syncPermissions();
    superAdminUser();
    DB::table('role_has_permissions')->insert(['role_id' => Level::superAdmin()->id, 'permission_id' => DB::table('permissions')->value('id')]);

    expect(fn () => $change(Level::superAdmin()))->toThrow(GuardRailViolation::class, 'Super Admin level')
        ->and(Level::superAdmin()->label)->toBe('Super Admin')
        ->and(Level::superAdmin()->users()->count())->toBe(1)
        ->and(DB::table('role_has_permissions')->count())->toBe(1);
})->with([
    'update' => [fn (Level $level) => $level->update(['label' => 'Raja'])],
    'delete' => [fn (Level $level) => $level->delete()],
]);

it('refuses to delete a level that has users', function (): void {
    $used = levelWith([], 'kasir');
    makeUser()->assignLevel($used);
    $unused = levelWith([], 'tamu');

    expect(fn () => $used->delete())->toThrow(GuardRailViolation::class, 'still has users')
        ->and($used->users()->count())->toBe(1)
        ->and($unused->delete())->toBeTrue()
        ->and(Level::query()->pluck('name')->all())->toBe(['kasir']);
});
