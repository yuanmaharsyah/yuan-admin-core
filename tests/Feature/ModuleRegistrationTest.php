<?php

declare(strict_types=1);

use Yuan\AdminCore\Tests\TestCase;

it('registers the service providers of enabled modules only', function (): void {
    expect(app()->bound('fixtures.demo.registered'))->toBeTrue()
        ->and(app()->bound('fixtures.off.registered'))->toBeFalse();
});

it('loads the migrations of every present module, disabled ones included', function (): void {
    expect(app('migrator')->paths())
        ->toContain(TestCase::FIXTURES.'/modules/Demo/Database/Migrations')
        ->toContain(TestCase::FIXTURES.'/modules/Off/Database/Migrations');
});
