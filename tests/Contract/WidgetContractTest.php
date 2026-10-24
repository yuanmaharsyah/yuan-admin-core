<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

uses(RefreshDatabase::class);

it('passes when every listed widget is a slug-prefixed Widget with a synced permission', function (): void {
    syncPermissions();

    ContractAssertions::assertWidgetContract();
});

it('fails on a class that is not a Widget, a key outside the slug, a duplicate key and an unsynced permission', function (): void {
    withBrokenModules();
    syncPermissions();

    expect(fn () => ContractAssertions::assertWidgetContract())->toThrow(AssertionFailedError::class, 'Leaky\Widgets\NotAWidget does not extend Yuan\AdminCore\Dashboard\Widget')
        ->and(fn () => ContractAssertions::assertWidgetContract())->toThrow(AssertionFailedError::class, 'Misnamed: key "other.total" must start with "leaky."')
        ->and(fn () => ContractAssertions::assertWidgetContract())->toThrow(AssertionFailedError::class, 'Duplicate: key "other.total" is already used by Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Widgets\Misnamed')
        ->and(fn () => ContractAssertions::assertWidgetContract())->toThrow(AssertionFailedError::class, 'Misnamed: permission "other.view" does not exist after permissions:sync');
});
