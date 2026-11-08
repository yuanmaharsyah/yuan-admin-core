<?php

declare(strict_types=1);

use Yuan\AdminCore\Registry\InvalidModuleDefinition;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Providers\DemoServiceProvider;
use Yuan\AdminCore\Tests\Fixtures\Widgets\CustomerTotal;
use Yuan\AdminCore\Tests\Fixtures\Widgets\InvoiceTotal;

const MODULE_FILE = '/app/modules/Invoice/module.php';
const GROUPS = ['beranda', 'master', 'keuangan', 'sistem'];

function definition(mixed $config): ModuleDefinition
{
    return ModuleDefinition::fromConfig($config, MODULE_FILE, 'project', 'Modules\\', GROUPS);
}

it('loads a valid module.php into typed properties', function (): void {
    $module = definition(moduleConfig(['widgets' => [InvoiceTotal::class]]));

    expect($module)->toEqual(new ModuleDefinition(
        name: 'Invoice',
        slug: 'invoices',
        label: 'Invoice',
        icon: 'receipt',
        group: 'keuangan',
        order: 20,
        enabled: true,
        core: false,
        superadminOnly: false,
        menu: true,
        actions: ['view', 'create', 'edit', 'delete', 'download'],
        widgets: [InvoiceTotal::class],
        path: '/app/modules/Invoice',
        root: 'project',
        namespace: 'Modules\\',
    ))->and($module->providerClass())->toBe('Modules\\Invoice\\Providers\\InvoiceServiceProvider');
});

it('loads the optional badge and submenu, defaulting a child to the view action', function (): void {
    $module = definition(moduleConfig([
        'badge' => 'Baru',
        'submenu' => [
            ['label' => 'Daftar', 'route' => 'index'],
            ['label' => 'Tambah', 'route' => 'create', 'action' => 'create', 'badge' => 'Baru'],
        ],
    ]));

    expect($module->badge)->toBe('Baru')
        ->and($module->submenu)->toBe([
            ['label' => 'Daftar', 'route' => 'index', 'action' => 'view', 'badge' => null, 'submenu' => []],
            ['label' => 'Tambah', 'route' => 'create', 'action' => 'create', 'badge' => 'Baru', 'submenu' => []],
        ])
        ->and(definition(moduleConfig())->submenu)->toBe([]);
});

it('loads a third menu level under a parent child without a route', function (): void {
    $module = definition(moduleConfig([
        'submenu' => [
            ['label' => 'Laporan', 'badge' => 'Baru', 'submenu' => [
                ['label' => 'Bulanan', 'route' => 'reports.monthly'],
                ['label' => 'Tahunan', 'route' => 'reports.yearly', 'action' => 'download'],
            ]],
        ],
    ]));

    expect($module->submenu)->toBe([
        ['label' => 'Laporan', 'route' => null, 'action' => 'view', 'badge' => 'Baru', 'submenu' => [
            ['label' => 'Bulanan', 'route' => 'reports.monthly', 'action' => 'view', 'badge' => null, 'submenu' => []],
            ['label' => 'Tahunan', 'route' => 'reports.yearly', 'action' => 'download', 'badge' => null, 'submenu' => []],
        ]],
    ]);
});

it('rejects a module.php that does not return an array', function (): void {
    definition(1);
})->throws(InvalidModuleDefinition::class, 'Invalid module definition in ['.MODULE_FILE.']: "module.php" must return an array.');

it('rejects a missing key', function (string $key): void {
    $config = moduleConfig();
    unset($config[$key]);

    definition($config);
})->with(['name', 'slug', 'label', 'icon', 'group', 'order', 'enabled', 'core', 'superadmin_only', 'menu', 'actions', 'widgets'])
    ->throws(InvalidModuleDefinition::class, 'is missing');

it('rejects an invalid value', function (array $overrides, string $message): void {
    expect(fn () => definition(moduleConfig($overrides)))
        ->toThrow(InvalidModuleDefinition::class, 'Invalid module definition in ['.MODULE_FILE.']: '.$message);
})->with([
    'name not PascalCase' => [['name' => 'invoice'], '"name" must be PascalCase.'],
    'name not the folder' => [['name' => 'Bill'], '"name" must equal the folder name "Invoice".'],
    'slug with underscore' => [['slug' => 'invoice_items'], '"slug" must be lowercase kebab-case.'],
    'empty label' => [['label' => ' '], '"label" must not be empty.'],
    'icon with the wrong case' => [['icon' => 'Receipt'], '"icon" must be a lucide icon name (resources/icons/lucide.json).'],
    'icon lucide does not have' => [['icon' => 'receipt-unicorn'], '"icon" must be a lucide icon name (resources/icons/lucide.json).'],
    'unknown group' => [['group' => 'gudang'], '"group" must be one of the configured menu groups (beranda, master, keuangan, sistem).'],
    'order as string' => [['order' => '20'], '"order" must be an integer.'],
    'enabled as string' => [['enabled' => 'yes'], '"enabled" must be true or false.'],
    'superadmin_only as int' => [['superadmin_only' => 1], '"superadmin_only" must be true or false.'],
    'actions without view' => [['actions' => ['create']], '"actions" must include "view".'],
    'action with a dash' => [['actions' => ['view', 'bulk-delete']], '"actions" may only contain single lowercase words.'],
    'action in capitals' => [['actions' => ['view', 'Approve']], '"actions" may only contain single lowercase words.'],
    'repeated action' => [['actions' => ['view', 'view']], '"actions" must not repeat "view".'],
    'actions as a map' => [['actions' => ['a' => 'view']], '"actions" must be a list.'],
    'core module disabled' => [['core' => true, 'enabled' => false], '"enabled" cannot be false for a core module.'],
    'unknown widget class' => [['widgets' => ['App\\Widgets\\Missing']], '"widgets" must only list existing classes.'],
    'widget that is no Widget' => [['widgets' => [DemoServiceProvider::class]], '"widgets" must only list Widget classes ('.DemoServiceProvider::class." isn't one)."],
    'badge too long' => [['badge' => str_repeat('x', 25)], '"badge" must be null or a short text (1 to 24 characters).'],
    'empty badge' => [['badge' => ' '], '"badge" must be null or a short text (1 to 24 characters).'],
    'submenu as a map' => [['submenu' => ['a' => []]], '"submenu" must be a list.'],
    'submenu child not an array' => [['submenu' => ['index']], '"submenu.0" must be an array with "label" and "route".'],
    'submenu child with an unknown key' => [['submenu' => [['label' => 'Daftar', 'route' => 'index', 'icon' => 'list']]], '"submenu.0" may only have "label", "route", "action" and "badge" (a "submenu" only on the second level: three levels at most).'],
    'parent child with a route' => [['submenu' => [['label' => 'Laporan', 'route' => 'index', 'submenu' => [['label' => 'Bulanan', 'route' => 'index']]]]], '"submenu.0" with a "submenu" is only a parent: "label", "badge" and "submenu", no "route" or "action".'],
    'parent child without links' => [['submenu' => [['label' => 'Laporan', 'submenu' => []]]], '"submenu.0.submenu" must not be empty.'],
    'a fourth level' => [['submenu' => [['label' => 'Laporan', 'submenu' => [['label' => 'Bulanan', 'submenu' => [['label' => 'Januari', 'route' => 'index']]]]]]], '"submenu.0.submenu.0" may only have "label", "route", "action" and "badge" (a "submenu" only on the second level: three levels at most).'],
    'third-level link with an undeclared action' => [['submenu' => [['label' => 'Laporan', 'submenu' => [['label' => 'Setujui', 'route' => 'index', 'action' => 'approve']]]]], '"submenu.0.submenu.0.action" must be one of the module\'s actions (view, create, edit, delete, download).'],
    'submenu child without label' => [['submenu' => [['route' => 'index']]], '"submenu.0.label" must not be empty.'],
    'submenu child with a full route name' => [['submenu' => [['label' => 'Daftar', 'route' => 'invoices.Index']]], '"submenu.0.route" must be a route name relative to the module (e.g. "index").'],
    'submenu child with an undeclared action' => [['submenu' => [['label' => 'Setujui', 'route' => 'index', 'action' => 'approve']]], '"submenu.0.action" must be one of the module\'s actions (view, create, edit, delete, download).'],
    'submenu child badge too long' => [['submenu' => [['label' => 'Daftar', 'route' => 'index', 'badge' => str_repeat('x', 25)]]], '"submenu.0.badge" must be null or a short text (1 to 24 characters).'],
    'widget key of another module' => [['widgets' => [CustomerTotal::class]], '"widgets" must have keys starting with "invoices." ('.CustomerTotal::class.' has "customers.total").'],
]);
