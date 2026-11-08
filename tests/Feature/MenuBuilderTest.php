<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Data\MenuChildData;
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

it('adds the badge and only the submenu children the user may open', function (): void {
    $root = temporaryRoot();
    writeModule($root, 'Invoice', [
        'badge' => 'Baru',
        'submenu' => [
            ['label' => 'Daftar', 'route' => 'index'],
            ['label' => 'Tambah', 'route' => 'create', 'action' => 'create', 'badge' => 'Baru'],
        ],
    ]);
    useModuleRoots([['name' => 'project', 'path' => $root, 'namespace' => 'Temp\\']]);
    Route::get('/admin/invoices', fn (): string => 'index')->name('invoices.index');
    Route::get('/admin/invoices/create', fn (): string => 'create')->name('invoices.create');
    app('router')->getRoutes()->refreshNameLookups();
    Gate::define('invoices.view', fn (): bool => true);
    $item = fn (): MenuItemData => app(MenuBuilder::class)->build(makeUser())[0]->items[0];

    expect($item()->badge)->toBe('Baru')
        ->and($item()->children)->toEqual([new MenuChildData('Daftar', url('/admin/invoices'), 'invoices.index')]);

    Gate::define('invoices.create', fn (): bool => true);

    expect($item()->children)->toEqual([
        new MenuChildData('Daftar', url('/admin/invoices'), 'invoices.index'),
        new MenuChildData('Tambah', url('/admin/invoices/create'), 'invoices.create', 'Baru'),
    ]);
});

it('nests the third level and drops a parent child whose links are all hidden', function (): void {
    $root = temporaryRoot();
    writeModule($root, 'Invoice', [
        'submenu' => [
            ['label' => 'Laporan', 'submenu' => [
                ['label' => 'Bulanan', 'route' => 'reports.monthly'],
                ['label' => 'Ekspor', 'route' => 'reports.export', 'action' => 'download'],
            ]],
            ['label' => 'Arsip', 'submenu' => [['label' => 'Ekspor arsip', 'route' => 'reports.export', 'action' => 'download']]],
        ],
    ]);
    useModuleRoots([['name' => 'project', 'path' => $root, 'namespace' => 'Temp\\']]);
    Route::get('/admin/invoices', fn (): string => 'index')->name('invoices.index');
    Route::get('/admin/invoices/reports/monthly', fn (): string => 'monthly')->name('invoices.reports.monthly');
    Route::get('/admin/invoices/reports/export', fn (): string => 'export')->name('invoices.reports.export');
    app('router')->getRoutes()->refreshNameLookups();
    Gate::define('invoices.view', fn (): bool => true);

    expect(app(MenuBuilder::class)->build(makeUser())[0]->items[0]->children)->toEqual([
        new MenuChildData('Laporan', null, null, null, [new MenuChildData('Bulanan', url('/admin/invoices/reports/monthly'), 'invoices.reports.monthly')]),
    ]);
});
