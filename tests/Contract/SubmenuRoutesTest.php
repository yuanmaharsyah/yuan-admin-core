<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

it('passes for the compliant modules', function (): void {
    ContractAssertions::assertSubmenuRoutes();
});

it('fails on a submenu child whose route lacks its permission', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertSubmenuRoutes())->toThrow(AssertionFailedError::class, 'Leaky: submenu route "leaky.index" lacks permission:leaky.view');
});

it('fails on a submenu child whose route does not exist', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertSubmenuRoutes())->toThrow(AssertionFailedError::class, 'Leaky: submenu route "leaky.archive" does not exist');
});

it('checks the links of the third level too', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertSubmenuRoutes())->toThrow(AssertionFailedError::class, 'Leaky: submenu route "leaky.reports.monthly" does not exist');
});
