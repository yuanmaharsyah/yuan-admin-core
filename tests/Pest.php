<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Permissions\PermissionSynchronizer;
use Yuan\AdminCore\Registry\ModuleRegistry;
use Yuan\AdminCore\Tests\ContractTestCase;
use Yuan\AdminCore\Tests\Fixtures\Models\User;
use Yuan\AdminCore\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(ContractTestCase::class)->in('Contract', 'Fixtures/Contract');

/**
 * @return list<array{name: string, path: string, namespace: string}>
 */
function fixtureRoots(): array
{
    return [
        ['name' => 'core', 'path' => TestCase::FIXTURES.'/Core', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Core\\'],
        ['name' => 'project', 'path' => TestCase::FIXTURES.'/Modules', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Modules\\'],
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
    return User::query()->create(['name' => $name, 'email' => Str::lower($name).'-'.Str::random(6).'@example.test', 'password' => 'Rahasia123', 'is_active' => true]);
}

function latestActivity(): ImmutableActivity
{
    return ImmutableActivity::query()->latest('id')->firstOrFail();
}

function syncPermissions(): void
{
    app(PermissionSynchronizer::class)->sync();
}

/**
 * A non-protected level holding exactly these (already synced) permissions.
 *
 * @param  list<string>  $permissions
 */
function levelWith(array $permissions, string $name = 'staf'): Level
{
    $level = Level::query()->create(['name' => $name, 'label' => Str::title($name)]);
    $level->syncPermissions($permissions);

    return $level;
}

function superAdminUser(string $name = 'Root'): User
{
    $user = makeUser($name);
    $user->assignLevel(Level::superAdmin());

    return $user;
}

/**
 * An artisan call with mocked console output.
 *
 * @param  array<string, mixed>  $parameters
 */
function command(string $name, array $parameters = []): PendingCommand
{
    $command = Pest\Laravel\artisan($name, $parameters);

    if (! $command instanceof PendingCommand) {
        throw new LogicException('Console output is not mocked.');
    }

    return $command;
}

/**
 * Point the registry at the given roots, as a project with those modules would.
 *
 * @param  list<array{name: string, path: string, namespace: string}>  $roots
 */
function useModuleRoots(array $roots): void
{
    app()->instance(ModuleRegistry::class, new ModuleRegistry(
        $roots,
        array_keys(config()->array('admin-core.menu.groups')),
        [],
        sys_get_temp_dir().'/admin-core-test-roots-'.getmypid().'.php',
    ));
}

/**
 * Adds the `Broken` root (module `Leaky`) next to the compliant one, with its
 * routes registered as the provider would.
 */
function withBrokenModules(): void
{
    useModuleRoots([ContractTestCase::CONTRACT_ROOT, ContractTestCase::BROKEN_ROOT]);

    /** @var list<string> $middleware */
    $middleware = config()->array('admin-core.routes.middleware');
    Route::middleware($middleware)->prefix('admin/leaky')->name('leaky.')->group(TestCase::FIXTURES.'/Broken/Leaky/Routes/web.php');
}

/**
 * A temp copy of a compliant fixture module, as the only module of a root.
 */
function copyOfContractModule(string $name): string
{
    $root = temporaryRoot();
    app('files')->copyDirectory(TestCase::FIXTURES."/Contract/{$name}", "{$root}/{$name}");
    useModuleRoots([['name' => 'project', 'path' => $root, 'namespace' => 'Copy\\']]);

    return "{$root}/{$name}";
}
