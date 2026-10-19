<?php

declare(strict_types=1);

use Yuan\AdminCore\Registry\InvalidModuleDefinition;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;
use Yuan\AdminCore\Tests\TestCase;

/**
 * @param  list<array{name: string, path: string, namespace: string}>  $roots
 * @param  list<string>  $disabled
 */
function registry(array $roots, array $disabled = []): ModuleRegistry
{
    return new ModuleRegistry($roots, ['beranda', 'master', 'keuangan', 'sistem'], $disabled, sys_get_temp_dir().'/admin-core-unused-cache.php');
}

/**
 * @param  list<ModuleDefinition>  $modules
 * @return list<string>
 */
function slugs(array $modules): array
{
    return array_map(fn (ModuleDefinition $module): string => $module->slug, $modules);
}

it('discovers the modules of both roots', function (): void {
    $registry = app(ModuleRegistry::class);

    expect(slugs($registry->all()))->toBe(['notes', 'admin-only', 'demo', 'hidden', 'off'])
        ->and($registry->find('notes')?->root)->toBe('core')
        ->and($registry->find('demo')?->root)->toBe('project');
});

it('leaves modules with enabled false out of the enabled list', function (): void {
    expect(slugs(app(ModuleRegistry::class)->enabled()))->toBe(['notes', 'admin-only', 'demo', 'hidden']);
});

it('turns off the core-root modules listed in admin-core.modules.disabled', function (): void {
    $core = temporaryRoot();
    writeModule($core, 'Downloads', ['slug' => 'downloads']);
    $roots = [['name' => 'core', 'path' => $core, 'namespace' => 'Yuan\\AdminCore\\Modules\\'], ...fixtureRoots()];

    $registry = registry($roots, disabled: ['downloads', 'demo']);

    expect(slugs($registry->enabled()))->toBe(['notes', 'admin-only', 'demo', 'hidden'])
        ->and($registry->find('downloads'))->not->toBeNull();
});

it('refuses to turn off a core module', function (): void {
    registry(fixtureRoots(), disabled: ['notes'])->all();
})->throws(InvalidModuleDefinition::class, 'Module "notes" in ['.TestCase::FIXTURES.'/core/Notes/module.php] is a core module');

it('fails on a slug used by two modules, naming both files', function (): void {
    $root = temporaryRoot();
    $first = writeModule($root, 'Bills', ['slug' => 'bills']);
    $second = writeModule($root, 'Invoices', ['slug' => 'bills']);

    expect(fn () => registry([['name' => 'project', 'path' => $root, 'namespace' => 'Modules\\']])->all())
        ->toThrow(InvalidModuleDefinition::class, "Duplicate module slug \"bills\" in [{$first}] and [{$second}].");
});

it('fails on a module name used in both roots', function (): void {
    $core = temporaryRoot();
    $project = temporaryRoot();
    $first = writeModule($core, 'Invoice', ['slug' => 'invoices']);
    $second = writeModule($project, 'Invoice', ['slug' => 'bills']);

    expect(fn () => registry([
        ['name' => 'core', 'path' => $core, 'namespace' => 'Yuan\\AdminCore\\Modules\\'],
        ['name' => 'project', 'path' => $project, 'namespace' => 'Modules\\'],
    ])->all())->toThrow(InvalidModuleDefinition::class, "Duplicate module name \"Invoice\" in [{$first}] and [{$second}].");
});

it('allows a project module to take the slug of a turned-off core module', function (): void {
    $core = temporaryRoot();
    $project = temporaryRoot();
    writeModule($core, 'Downloads', ['slug' => 'downloads']);
    writeModule($project, 'Exports', ['slug' => 'downloads']);

    $registry = registry([
        ['name' => 'core', 'path' => $core, 'namespace' => 'Yuan\\AdminCore\\Modules\\'],
        ['name' => 'project', 'path' => $project, 'namespace' => 'Modules\\'],
    ], disabled: ['downloads']);

    expect(array_map(fn (ModuleDefinition $module): string => $module->name, $registry->enabled()))->toBe(['Exports']);
});

it('boots with a missing or empty root', function (): void {
    expect(registry([
        ['name' => 'core', 'path' => '/does/not/exist', 'namespace' => 'Yuan\\AdminCore\\Modules\\'],
        ['name' => 'project', 'path' => temporaryRoot(), 'namespace' => 'Modules\\'],
    ])->all())->toBe([]);
});
