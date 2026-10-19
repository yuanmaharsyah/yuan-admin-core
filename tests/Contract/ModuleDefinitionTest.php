<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

it('passes for the compliant modules', function (): void {
    ContractAssertions::assertModuleDefinitionsValid();
});

it('fails on an invalid module.php even when the registry is cached', function (): void {
    $root = temporaryRoot();
    writeModule($root, 'Invoice', ['actions' => ['create']]);
    useModuleRoots([['name' => 'project', 'path' => $root, 'namespace' => 'Temp\\']]);

    expect(fn () => ContractAssertions::assertModuleDefinitionsValid())->toThrow(AssertionFailedError::class, 'must include "view"');
});

it('fails on a duplicate slug across roots', function (): void {
    [$first, $second] = [temporaryRoot(), temporaryRoot()];
    writeModule($first, 'Invoice');
    writeModule($second, 'Bill', ['slug' => 'invoices']);
    useModuleRoots([['name' => 'core', 'path' => $first, 'namespace' => 'A\\'], ['name' => 'project', 'path' => $second, 'namespace' => 'B\\']]);

    expect(fn () => ContractAssertions::assertModuleDefinitionsValid())->toThrow(AssertionFailedError::class, 'Duplicate slug "invoices"');
});
