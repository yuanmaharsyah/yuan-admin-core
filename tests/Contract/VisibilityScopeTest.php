<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

it('passes when every module model uses HasVisibilityScope', function (): void {
    ContractAssertions::assertVisibilityScope();
});

it('fails on a module model without HasVisibilityScope', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertVisibilityScope())
        ->toThrow(AssertionFailedError::class, 'Leaky\Models\Leaky: does not use HasVisibilityScope');
});
