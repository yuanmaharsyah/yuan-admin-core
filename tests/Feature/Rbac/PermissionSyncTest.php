<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Yuan\AdminCore\Permissions\PermissionSynchronizer;

uses(RefreshDatabase::class);

it('creates exactly the permissions of enabled, non-superadmin_only modules', function (): void {
    expect(app(PermissionSynchronizer::class)->sync())->toBe(['created' => 5, 'removed' => 0])
        ->and(Permission::query()->orderBy('name')->get(['name', 'module', 'action'])->toArray())->toBe([
            ['name' => 'demo.approve', 'module' => 'demo', 'action' => 'approve'],
            ['name' => 'demo.create', 'module' => 'demo', 'action' => 'create'],
            ['name' => 'demo.view', 'module' => 'demo', 'action' => 'view'],
            ['name' => 'hidden.view', 'module' => 'hidden', 'action' => 'view'],
            ['name' => 'notes.view', 'module' => 'notes', 'action' => 'view'],
        ]);
});

it('is idempotent', function (): void {
    syncPermissions();

    expect(app(PermissionSynchronizer::class)->sync())->toBe(['created' => 0, 'removed' => 0])
        ->and(Permission::query()->count())->toBe(5);
});

it('removes orphans together with their grants', function (): void {
    syncPermissions();
    Permission::query()->create(['name' => 'gone.view', 'guard_name' => 'web', 'module' => 'gone', 'action' => 'view']);
    $level = levelWith(['gone.view', 'demo.view']);

    expect(app(PermissionSynchronizer::class)->sync())->toBe(['created' => 0, 'removed' => 1])
        ->and($level->permissions()->pluck('name')->all())->toBe(['demo.view']);
});

it('resets the permission cache', function (): void {
    syncPermissions();
    app(PermissionRegistrar::class)->getPermissions();
    expect(Cache::has(config('permission.cache.key')))->toBeTrue();

    syncPermissions();

    expect(Cache::has(config('permission.cache.key')))->toBeFalse();
});

it('runs as permissions:sync', function (): void {
    command('permissions:sync')->expectsOutputToContain('5 created, 0 removed')->assertSuccessful();
});
