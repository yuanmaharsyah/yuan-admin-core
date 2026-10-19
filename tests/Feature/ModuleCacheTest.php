<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Yuan\AdminCore\Registry\InvalidModuleDefinition;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;

it('caches the module definitions and reads them without scanning', function (): void {
    $cache = config()->string('admin-core.modules.cache_path');

    expect(Artisan::call('modules:cache'))->toBe(0);

    $fromCache = new ModuleRegistry([], ['beranda', 'master', 'keuangan', 'sistem'], [], $cache);

    expect($cache)->toBeFile()
        ->and(array_map(fn (ModuleDefinition $module): string => $module->slug, $fromCache->all()))
        ->toBe(['notes', 'admin-only', 'demo', 'hidden', 'off']);
});

it('clears the module cache', function (): void {
    Artisan::call('modules:cache');

    expect(Artisan::call('modules:clear'))->toBe(0)
        ->and(config()->string('admin-core.modules.cache_path'))->not->toBeFile();
});

it('hooks into optimize and optimize:clear', function (): void {
    expect(ServiceProvider::$optimizeCommands)->toHaveKey('modules', 'modules:cache')
        ->and(ServiceProvider::$optimizeClearCommands)->toHaveKey('modules', 'modules:clear');
});

it('never caches an invalid module', function (): void {
    $root = temporaryRoot();
    writeModule($root, 'Invoice', ['group' => 'gudang']);
    $cache = sys_get_temp_dir().'/admin-core-invalid-cache.php';
    $registry = new ModuleRegistry([['name' => 'project', 'path' => $root, 'namespace' => 'Modules\\']], ['master'], [], $cache);

    expect(fn () => $registry->cache())->toThrow(InvalidModuleDefinition::class)
        ->and($cache)->not->toBeFile();
});
