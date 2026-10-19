<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('boots a Laravel 13 application on in-memory SQLite', function (): void {
    expect(app()->version())->toStartWith('13.')
        ->and(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:');
});
