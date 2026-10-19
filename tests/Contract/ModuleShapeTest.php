<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

it('passes for the compliant modules and counts them', function (): void {
    expect(ContractAssertions::assertModuleShape())->toBe(2);
});

it('fails when a required file is missing', function (string $file): void {
    $module = copyOfContractModule('Invoice');
    unlink("{$module}/{$file}");

    expect(fn () => ContractAssertions::assertModuleShape())->toThrow(AssertionFailedError::class, "Invoice: missing {$file}");
})->with([
    'always: routes' => 'Routes/web.php',
    'always: tests' => 'Tests/Feature/InvoiceTest.php',
    'model: policy' => 'Policies/InvoicePolicy.php',
    'model: list item data' => 'Data/InvoiceListItemData.php',
    'create: store data' => 'Data/StoreInvoiceData.php',
    'edit: edit page' => 'resources/js/pages/Edit.tsx',
    'create or edit: form' => 'resources/js/components/InvoiceForm.tsx',
    'download: export' => 'Exports/InvoiceExport.php',
]);

it('asks a module without a model for the base files only', function (): void {
    $module = copyOfContractModule('Customer');
    array_map(app('files')->deleteDirectory(...), ["{$module}/Models", "{$module}/Policies", "{$module}/Data"]);

    expect(ContractAssertions::assertModuleShape())->toBe(1);

    unlink("{$module}/Tests/Feature/CustomerTest.php");

    expect(fn () => ContractAssertions::assertModuleShape())->toThrow(AssertionFailedError::class, 'Customer: missing Tests/Feature/CustomerTest.php');
});

it('reports the broken module', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertModuleShape())->toThrow(AssertionFailedError::class, 'Leaky: missing resources/js/pages/Index.tsx');
});
