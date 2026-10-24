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

it('fails on a key two widgets share and a permission permissions:sync never makes', function (): void {
    withBrokenModules();
    syncPermissions();

    expect(fn () => ContractAssertions::assertWidgetContract())->toThrow(AssertionFailedError::class, 'Duplicate: key "leaky.total" is already used by Yuan\\AdminCore\\Tests\\Fixtures\\Broken\\Leaky\\Widgets\\Unsynced')
        ->and(fn () => ContractAssertions::assertWidgetContract())->toThrow(AssertionFailedError::class, 'Unsynced: permission "leaky.export" does not exist after permissions:sync');
});
