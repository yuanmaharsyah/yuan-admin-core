<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Yuan\AdminCore\Testing\ContractAssertions;
use Yuan\AdminCore\Tests\TestCase;

it('passes for the compliant modules', function (): void {
    ContractAssertions::assertPolicyCoverage();
});

it('fails when a declared action has no policy method', function (): void {
    withBrokenModules();

    expect(fn () => ContractAssertions::assertPolicyCoverage())->toThrow(AssertionFailedError::class, 'LeakyPolicy::approve() missing for action "approve"');
});

it('skips superadmin_only modules, which have no permissions', function (): void {
    useModuleRoots([['name' => 'project', 'path' => TestCase::FIXTURES.'/Modules', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Modules\\']]);

    expect(collect(ContractAssertions::contractModules())->firstWhere('slug', 'admin-only')?->superadminOnly)->toBeTrue();
    ContractAssertions::assertPolicyCoverage();
});
