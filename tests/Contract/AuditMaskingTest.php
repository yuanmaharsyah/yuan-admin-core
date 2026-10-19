<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\Testing\ContractAssertions;

uses(RefreshDatabase::class);

it('masks every configured field for every event type', function (): void {
    ContractAssertions::assertMaskingHolds();
});

it('covers a field a project adds, with no test edit', function (): void {
    config(['admin-core.audit.masked_fields.no_bpjs' => 'partial']);
    app()->forgetInstance(Masker::class);

    ContractAssertions::assertMaskingHolds();
});

it('fails when a configured field reaches the row unmasked', function (): void {
    app(Masker::class); // the writer keeps the rules it was built with, as a stale or bypassed masker would
    config(['admin-core.audit.masked_fields.no_bpjs' => 'full']);

    expect(fn () => ContractAssertions::assertMaskingHolds())->toThrow(AssertionFailedError::class, 'no_bpjs (full) not masked in a [created] row');
});
