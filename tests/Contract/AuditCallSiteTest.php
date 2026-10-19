<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;

it('passes for core src and the compliant modules', function (): void {
    ContractAssertions::assertAuditCallSites();
});

it('fails on an event that is neither taxonomy nor a declared action', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertAuditCallSites())->toThrow(AssertionFailedError::class, 'LeakyController.php:16: Audit::record(publish)');
});

it('allows a variable event only inside core code', function (): void {
    $dir = temporaryRoot();
    file_put_contents("{$dir}/Caller.php", '<?php Audit::record($event); Audit::record(event: AuditEvent::Login); Audit::record("created");');

    expect(fn () => ContractAssertions::assertAuditCallSites([$dir]))->toThrow(AssertionFailedError::class, 'Caller.php:1: Audit::record($event)');
});
