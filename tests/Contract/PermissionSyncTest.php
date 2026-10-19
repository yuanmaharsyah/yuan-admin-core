<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\AssertionFailedError;
use Spatie\Permission\Models\Permission;
use Yuan\AdminCore\Testing\ContractAssertions;

uses(RefreshDatabase::class);

beforeEach(fn () => syncPermissions());

it('passes after permissions:sync', function (): void {
    ContractAssertions::assertPermissionsInSync();
});

it('fails on a missing permission', function (): void {
    Permission::query()->where('name', 'invoices.approve')->delete();

    expect(fn () => ContractAssertions::assertPermissionsInSync())->toThrow(AssertionFailedError::class, 'missing invoices.approve');
});

it('fails on an orphan permission', function (): void {
    Permission::query()->create(['name' => 'invoices.publish', 'guard_name' => 'web', 'module' => 'invoices', 'action' => 'publish']);

    expect(fn () => ContractAssertions::assertPermissionsInSync())->toThrow(AssertionFailedError::class, 'orphan invoices.publish');
});
