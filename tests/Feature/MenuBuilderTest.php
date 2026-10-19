<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Data\MenuGroupData;
use Yuan\AdminCore\Data\MenuItemData;
use Yuan\AdminCore\Registry\MenuBuilder;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

uses(RefreshDatabase::class);

/**
 * @return array<string, list<string>>
 */
function menuFor(User $user): array
{
    $menu = [];

    foreach (app(MenuBuilder::class)->build($user) as $group) {
        $menu[$group->key] = array_map(fn (MenuItemData $item): string => $item->slug, $group->items);
    }

    return $menu;
}

it('shows nothing without permissions', function (): void {
    expect(menuFor(makeUser()))->toBe([]);
});

it('shows only the modules the user may view, grouped in config order', function (): void {
    Gate::define('demo.view', fn (): bool => true);
    Gate::define('notes.view', fn (): bool => true);
    Gate::define('hidden.view', fn (): bool => true);

    expect(menuFor(makeUser()))->toBe([
        'master' => ['demo'],
        'sistem' => ['notes'],
    ]);
});

it('shows superadmin_only modules to Super Admin, ordered by order then label', function (): void {
    Gate::before(fn (): bool => true);

    expect(menuFor(makeUser()))->toBe([
        'master' => ['admin-only', 'demo'],
        'sistem' => ['notes'],
    ]);
});

it('adds the create link only when the route exists and the user may create', function (): void {
    Gate::define('demo.view', fn (): bool => true);
    $hrefOf = fn (): ?string => app(MenuBuilder::class)->build(makeUser())[0]->items[0]->create_href;

    expect($hrefOf())->toBeNull();

    Route::get('/admin/demo/create', fn (): string => 'create')->name('demo.create');
    app('router')->getRoutes()->refreshNameLookups();

    expect($hrefOf())->toBeNull();

    Gate::define('demo.create', fn (): bool => true);

    expect($hrefOf())->toBe(url('/admin/demo/create'));
});

it('builds groups and items with label, icon and link', function (): void {
    Gate::define('demo.view', fn (): bool => true);

    expect(app(MenuBuilder::class)->build(makeUser()))->toEqual([
        new MenuGroupData('master', 'Master Data', 'database', [
            new MenuItemData('demo', 'Demo', 'receipt', url('/admin/demo')),
        ]),
    ]);
});
