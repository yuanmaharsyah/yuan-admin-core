<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

it('passes for the compliant modules', function (): void {
    ContractAssertions::assertAdminRoutesProtected();
});

it('fails on a module route without a permission middleware', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertAdminRoutesProtected())->toThrow(AssertionFailedError::class, 'GET|HEAD /admin/leaky: missing permission:{slug}.{action} or superadmin');
});

it('fails on an admin route without auth', function (): void {
    Route::middleware(['web', 'permission:invoices.view'])->get('admin/invoices/open', fn (): string => 'open');

    expect(fn () => ContractAssertions::assertAdminRoutesProtected())->toThrow(AssertionFailedError::class, 'GET|HEAD /admin/invoices/open: missing auth');
});
