<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('lists every module with its root, actions and status', function (): void {
    expect(Artisan::call('modules:list'))->toBe(0);

    $rows = array_map(
        fn (string $line): array => array_values(array_filter(array_map(trim(...), explode('|', $line)), fn (string $cell): bool => $cell !== '')),
        array_filter(explode(PHP_EOL, Artisan::output()), fn (string $line): bool => str_starts_with($line, '|')),
    );

    expect(array_values($rows))->toBe([
        ['Module', 'Root', 'Slug', 'Group', 'Actions', 'Enabled', 'Superadmin only'],
        ['Notes', 'core', 'notes', 'sistem', 'view', 'yes', 'no'],
        ['AdminOnly', 'project', 'admin-only', 'master', 'view', 'yes', 'yes'],
        ['Demo', 'project', 'demo', 'master', 'view, create, approve', 'yes', 'no'],
        ['Hidden', 'project', 'hidden', 'master', 'view', 'yes', 'no'],
        ['Off', 'project', 'off', 'master', 'view', 'no', 'no'],
    ]);
});
