<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Yuan\AdminCore\Registry\MenuBuilder;
use Yuan\AdminCore\Registry\MenuGroup;
use Yuan\AdminCore\Registry\MenuItem;

/**
 * @return array<string, list<string>>
 */
function menuFor(GenericUser $user): array
{
    $menu = [];

    foreach (app(MenuBuilder::class)->build($user) as $group) {
        $menu[$group->key] = array_map(fn (MenuItem $item): string => $item->slug, $group->items);
    }

    return $menu;
}

it('shows nothing without permissions', function (): void {
    expect(menuFor(new GenericUser(['id' => 1])))->toBe([]);
});

it('shows only the modules the user may view, grouped in config order', function (): void {
    Gate::define('demo.view', fn (): bool => true);
    Gate::define('notes.view', fn (): bool => true);
    Gate::define('hidden.view', fn (): bool => true);

    expect(menuFor(new GenericUser(['id' => 1])))->toBe([
        'master' => ['demo'],
        'sistem' => ['notes'],
    ]);
});

it('shows superadmin_only modules to Super Admin, ordered by order then label', function (): void {
    Gate::before(fn (): bool => true);

    expect(menuFor(new GenericUser(['id' => 1])))->toBe([
        'master' => ['admin-only', 'demo'],
        'sistem' => ['notes'],
    ]);
});

it('builds groups and items with label, icon and link', function (): void {
    Gate::define('demo.view', fn (): bool => true);

    expect(app(MenuBuilder::class)->build(new GenericUser(['id' => 1])))->toEqual([
        new MenuGroup('master', 'Master Data', 'database', [
            new MenuItem('demo', 'Demo', 'receipt', url('/admin/demo')),
        ]),
    ]);
});
