<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Models\User;
use Yuan\AdminCore\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * @return list<array{name: string, path: string, namespace: string}>
 */
function fixtureRoots(): array
{
    return [
        ['name' => 'core', 'path' => TestCase::FIXTURES.'/core', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Core\\'],
        ['name' => 'project', 'path' => TestCase::FIXTURES.'/modules', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Modules\\'],
    ];
}

/**
 * A valid module.php array, with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function moduleConfig(array $overrides = []): array
{
    return [
        'name' => 'Invoice',
        'slug' => 'invoices',
        'label' => 'Invoice',
        'icon' => 'receipt',
        'group' => 'keuangan',
        'order' => 20,
        'enabled' => true,
        'core' => false,
        'superadmin_only' => false,
        'menu' => true,
        'actions' => ['view', 'create', 'edit', 'delete', 'download'],
        'widgets' => [],
        ...$overrides,
    ];
}

/**
 * A fresh, empty module root in the system temp dir.
 */
function temporaryRoot(): string
{
    $root = sys_get_temp_dir().'/admin-core-root-'.Str::random(8);
    mkdir($root);

    return $root;
}

/**
 * Write `{root}/{name}/module.php` and return its path.
 *
 * @param  array<string, mixed>  $overrides
 */
function writeModule(string $root, string $name, array $overrides = []): string
{
    $file = "{$root}/{$name}/module.php";
    is_dir(dirname($file)) || mkdir(dirname($file), recursive: true);
    file_put_contents($file, '<?php return '.var_export(moduleConfig(['name' => $name, ...$overrides]), true).';');

    return $file;
}

/**
 * A persisted fixture user (audit causer / subject).
 */
function makeUser(string $name = 'Budi'): User
{
    return User::query()->create(['name' => $name, 'email' => Str::lower($name).'-'.Str::random(6).'@example.test', 'password' => 'Rahasia123']);
}

function latestActivity(): ImmutableActivity
{
    return ImmutableActivity::query()->latest('id')->firstOrFail();
}
