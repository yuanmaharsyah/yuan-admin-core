<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

uses(RefreshDatabase::class);

it('passes for audited models and a NotAudited lookup with a reason', function (): void {
    ContractAssertions::assertAuditCoverage();
});

it('fails on an unaudited model and on an empty NotAudited reason', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertAuditCoverage())
        ->toThrow(AssertionFailedError::class, 'Leaky\Models\Leaky: has business columns but no LogsModuleActivity')
        ->and(fn () => ContractAssertions::assertAuditCoverage())
        ->toThrow(AssertionFailedError::class, 'Leaky\Models\Draft: #[NotAudited] needs a reason');
});
