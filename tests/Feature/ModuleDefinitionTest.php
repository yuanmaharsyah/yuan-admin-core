<?php

declare(strict_types=1);

use Yuan\AdminCore\Registry\InvalidModuleDefinition;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Providers\DemoServiceProvider;

const MODULE_FILE = '/app/modules/Invoice/module.php';
const GROUPS = ['beranda', 'master', 'keuangan', 'sistem'];

function definition(mixed $config): ModuleDefinition
{
    return ModuleDefinition::fromConfig($config, MODULE_FILE, 'project', 'Modules\\', GROUPS);
}

it('loads a valid module.php into typed properties', function (): void {
    $module = definition(moduleConfig(['widgets' => [DemoServiceProvider::class]]));

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
        widgets: [DemoServiceProvider::class],
        path: '/app/modules/Invoice',
        root: 'project',
        namespace: 'Modules\\',
    ))->and($module->providerClass())->toBe('Modules\\Invoice\\Providers\\InvoiceServiceProvider');
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
    'icon not kebab' => [['icon' => 'Receipt'], '"icon" must be a lowercase kebab-case lucide icon name.'],
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
]);
