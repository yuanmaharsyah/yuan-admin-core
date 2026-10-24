<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use SplFileInfo;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Audit\LogsModuleActivity;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\Audit\NotAudited;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Registry\InvalidModuleDefinition;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * The runtime contract checks of spec §9.1, called from Pest tests as
 * `ContractAssertions::assertModuleShape()`. Each one reads the current app's
 * module registry, so the same call checks the package fixtures and a
 * project's modules, and reports every violation at once.
 */
final class ContractAssertions
{
    /**
     * Every module on disk, enabled or not: a disabled module still keeps the
     * contract, so it can be turned back on.
     *
     * @return list<ModuleDefinition>
     */
    public static function contractModules(): array
    {
        return app(ModuleRegistry::class)->all();
    }

    /**
     * Every module has the files spec §3 requires for its declared actions.
     *
     * @return int The number of modules checked.
     */
    public static function assertModuleShape(): int
    {
        $violations = [];
        $modules = self::contractModules();

        foreach ($modules as $module) {
            foreach (self::requiredFiles($module) as $file) {
                if (! is_file($module->path($file))) {
                    $violations[] = "{$module->name}: missing {$file}";
                }
            }
        }

        self::failOnViolations('Module shape (spec §3)', $violations);

        return count($modules);
    }

    /**
     * Every `module.php` on disk is valid, and slugs and names are unique,
     * read again from disk so a stale module cache can't hide a break.
     */
    public static function assertModuleDefinitionsValid(): void
    {
        $violations = [];
        $seen = [];
        /** @var array<string, mixed> $groups */
        $groups = config()->array('admin-core.menu.groups');
        /** @var list<string> $disabled */
        $disabled = config()->array('admin-core.modules.disabled');

        foreach (app(ModuleRegistry::class)->scan() as $entry) {
            try {
                $module = ModuleDefinition::fromConfig($entry['config'], $entry['file'], $entry['root'], $entry['namespace'], array_keys($groups));
            } catch (InvalidModuleDefinition $exception) {
                $violations[] = $exception->getMessage();

                continue;
            }

            if ($module->root === ModuleRegistry::CORE_ROOT && in_array($module->slug, $disabled, true)) {
                continue;
            }

            foreach (['slug' => $module->slug, 'name' => $module->name] as $key => $value) {
                if (isset($seen[$key][$value])) {
                    $violations[] = "Duplicate {$key} \"{$value}\": {$seen[$key][$value]} and {$entry['file']}";
                }

                $seen[$key][$value] = $entry['file'];
            }
        }

        self::failOnViolations('Module definitions (spec §3.1)', $violations);
    }

    /**
     * Every route under the admin prefix has `auth` and a `permission:` or
     * `superadmin` middleware (spec §8).
     */
    public static function assertAdminRoutesProtected(): void
    {
        $violations = [];
        $prefix = trim(config()->string('admin-core.routes.prefix'), '/');

        /** @var Route $route */
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $uri = trim($route->uri(), '/');

            if ($uri !== $prefix && ! str_starts_with($uri, $prefix.'/')) {
                continue;
            }

            $middleware = array_filter($route->gatherMiddleware(), is_string(...));
            $name = implode('|', $route->methods())." /{$uri}";

            if (! array_any($middleware, fn (string $m): bool => $m === 'auth' || str_starts_with($m, 'auth:'))) {
                $violations[] = "{$name}: missing auth";
            }

            if (! array_any($middleware, fn (string $m): bool => $m === 'superadmin' || str_starts_with($m, 'permission:'))) {
                $violations[] = "{$name}: missing permission:{slug}.{action} or superadmin";
            }
        }

        self::failOnViolations('Admin route protection (spec §8)', $violations);
    }

    /**
     * The policy of each module's main model has a method for every declared
     * action (`view` → `viewAny` + `view`, `edit` → `update`, others by name).
     */
    public static function assertPolicyCoverage(): void
    {
        $violations = [];

        foreach (self::contractModules() as $module) {
            $model = "{$module->namespace}{$module->name}\\Models\\{$module->name}";

            if ($module->superadminOnly || ! class_exists($model)) {
                continue;
            }

            $policy = Gate::getPolicyFor($model);

            if (! is_object($policy)) {
                $violations[] = "{$module->name}: no policy for {$model}";

                continue;
            }

            foreach ($module->actions as $action) {
                foreach (match ($action) {
                    'view' => ['viewAny', 'view'],
                    'edit' => ['update'],
                    default => [$action],
                } as $method) {
                    if (! method_exists($policy, $method)) {
                        $violations[] = "{$module->name}: ".$policy::class."::{$method}() missing for action \"{$action}\"";
                    }
                }
            }
        }

        self::failOnViolations('Policy coverage (spec §9.1)', $violations);
    }

    /**
     * The `permissions` table holds exactly `{slug}.{action}` of every enabled,
     * non-superadmin_only module (spec §6.2).
     */
    public static function assertPermissionsInSync(): void
    {
        $wanted = [];

        foreach (app(ModuleRegistry::class)->enabled() as $module) {
            if (! $module->superadminOnly) {
                foreach ($module->actions as $action) {
                    $wanted[] = "{$module->slug}.{$action}";
                }
            }
        }

        /** @var list<string> $actual */
        $actual = Permission::query()->where('guard_name', config()->string('auth.defaults.guard'))->pluck('name')->all();
        $violations = [
            ...array_map(fn (string $name): string => "missing {$name}", array_values(array_diff($wanted, $actual))),
            ...array_map(fn (string $name): string => "orphan {$name}", array_values(array_diff($actual, $wanted))),
        ];

        self::failOnViolations('Permissions out of sync with the module configs; run permissions:sync', $violations);
    }

    /**
     * Every widget listed in a `module.php` extends `Widget`, has a unique key
     * prefixed with its module's slug, and its permission exists after
     * `permissions:sync` (spec §7.2).
     */
    public static function assertWidgetContract(): void
    {
        $violations = [];
        $keys = [];
        /** @var list<string> $permissions */
        $permissions = Permission::query()->pluck('name')->all();

        foreach (app(ModuleRegistry::class)->enabled() as $module) {
            foreach ($module->widgets as $class) {
                $widget = is_subclass_of($class, Widget::class) ? app($class) : null;

                if (! $widget instanceof Widget) {
                    $violations[] = "{$module->name}: {$class} does not extend ".Widget::class;

                    continue;
                }

                $key = $widget->key();

                if (! str_starts_with($key, "{$module->slug}.")) {
                    $violations[] = "{$class}: key \"{$key}\" must start with \"{$module->slug}.\"";
                }

                if (isset($keys[$key])) {
                    $violations[] = "{$class}: key \"{$key}\" is already used by {$keys[$key]}";
                }

                $keys[$key] = $class;

                if (! in_array($widget->permission(), $permissions, true)) {
                    $violations[] = "{$class}: permission \"{$widget->permission()}\" does not exist after permissions:sync";
                }
            }
        }

        self::failOnViolations('Widget contract (spec §7.2)', $violations);
    }

    /**
     * Permissions only ever reach a user through their level (AGENTS §4).
     */
    public static function assertNoDirectPermissions(): void
    {
        $rows = DB::table(config()->string('permission.table_names.model_has_permissions'))->count();

        Assert::assertSame(0, $rows, "model_has_permissions has {$rows} row(s): permissions must go through a level.");
    }

    /**
     * Every module model whose table has a business column uses
     * `LogsModuleActivity`, or opts out with `#[NotAudited(reason: '...')]`.
     */
    public static function assertAuditCoverage(): void
    {
        $violations = [];

        foreach (self::moduleModels() as $class) {
            $violation = self::auditCoverageViolation(new $class);

            if ($violation !== null) {
                $violations[] = "{$class}: {$violation}";
            }
        }

        self::failOnViolations('Audit coverage (spec §9.1)', $violations);
    }

    /**
     * Every module model uses `HasVisibilityScope`, so every list and show
     * query can call `->visibleTo($user)` (spec §7, data scope hook).
     */
    public static function assertVisibilityScope(): void
    {
        $violations = [];

        foreach (self::moduleModels() as $class) {
            if (! in_array(HasVisibilityScope::class, class_uses_recursive($class), true)) {
                $violations[] = "{$class}: does not use HasVisibilityScope";
            }
        }

        self::failOnViolations('Data scope hook (spec §7)', $violations);
    }

    /**
     * The first argument of every `Audit::record()` is an `AuditEvent` case, a
     * taxonomy name, or a custom action declared by the file's module. Only core
     * code may pass a variable.
     *
     * @param  list<string>  $paths  Extra directories to scan (a project's `app/`); core `src/` and every module are always scanned.
     */
    public static function assertAuditCallSites(array $paths = []): void
    {
        $src = dirname(__DIR__);
        $violations = [];

        foreach ([$src, ...$paths, ...array_map(fn (ModuleDefinition $module): string => $module->path, self::contractModules())] as $path) {
            foreach (self::phpFiles($path) as $file) {
                $module = array_find(self::contractModules(), fn (ModuleDefinition $m): bool => str_starts_with($file, $m->path.'/'));

                foreach (self::recordCalls($file) as [$line, $kind, $value]) {
                    $valid = match ($kind) {
                        'case' => true,
                        'string' => AuditEvent::tryFrom($value) !== null || in_array($value, $module?->customActions() ?? [], true),
                        default => str_starts_with($file, $src.'/'),
                    };

                    if (! $valid) {
                        $violations[] = "{$file}:{$line}: Audit::record({$value}) is neither an AuditEvent nor a custom action declared by ".($module->name ?? 'a module');
                    }
                }
            }
        }

        self::failOnViolations('Audit call sites (spec §9.1)', $violations);
    }

    /**
     * Every field in `audit.masked_fields` is omitted or masked in the stored
     * row for every event type (a `*` in a pattern is tested as `sample`).
     */
    public static function assertMaskingHolds(): void
    {
        $violations = [];

        foreach (config()->array('admin-core.audit.masked_fields') as $pattern => $rule) {
            $field = str_replace('*', 'sample', (string) $pattern);

            foreach (AuditEvent::cases() as $event) {
                $raw = 'ABCD'.Str::random(12).'WXYZ';
                Audit::record($event, meta: [$field => $raw]);
                $row = ImmutableActivity::query()->latest('id')->firstOrFail();
                $meta = $row->getProperty('meta');
                $stored = json_encode(DB::table($row->getTable())->where('id', $row->getKey())->first(), JSON_THROW_ON_ERROR);

                $masked = match ($rule) {
                    'omit' => ! is_array($meta) || ! array_key_exists($field, $meta),
                    'partial' => is_array($meta) && ($meta[$field] ?? null) === 'ABCD'.Masker::HIDDEN.'WXYZ',
                    default => is_array($meta) && ($meta[$field] ?? null) === Masker::HIDDEN,
                };

                if (! $masked || str_contains($stored, $raw)) {
                    $violations[] = "{$field} (".(is_string($rule) ? $rule : '?').") not masked in a [{$event->value}] row";
                }
            }
        }

        self::failOnViolations('Audit masking (spec §7.1)', $violations);
    }

    /**
     * @return list<string> Paths relative to the module folder.
     */
    private static function requiredFiles(ModuleDefinition $module): array
    {
        $name = $module->name;
        $files = ['module.php', 'Routes/web.php', "Http/Controllers/{$name}Controller.php", 'resources/js/pages/Index.tsx', "Tests/Feature/{$name}Test.php"];
        $declares = fn (string $action): bool => in_array($action, $module->actions, true);

        if ($declares('download')) {
            $files[] = "Exports/{$name}Export.php";
        }

        if (! is_file($module->path("Models/{$name}.php"))) {
            return $files;
        }

        $files = [...$files, "Policies/{$name}Policy.php", "Data/{$name}Data.php", "Data/{$name}ListItemData.php"];

        if ($declares('create')) {
            $files = [...$files, "Data/Store{$name}Data.php", 'resources/js/pages/Create.tsx'];
        }

        if ($declares('edit')) {
            $files = [...$files, "Data/Update{$name}Data.php", 'resources/js/pages/Edit.tsx'];
        }

        if ($declares('create') || $declares('edit')) {
            $files[] = "resources/js/components/{$name}Form.tsx";
        }

        return $files;
    }

    /**
     * The concrete Eloquent models in every module's `Models/` folder.
     *
     * @return list<class-string<Model>>
     */
    private static function moduleModels(): array
    {
        $models = [];

        foreach (self::contractModules() as $module) {
            foreach (glob($module->path('Models/*.php')) ?: [] as $file) {
                $class = "{$module->namespace}{$module->name}\\Models\\".basename($file, '.php');

                if (class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract()) {
                    $models[] = $class;
                }
            }
        }

        return $models;
    }

    private static function auditCoverageViolation(Model $model): ?string
    {
        $schema = Schema::connection($model->getConnectionName());

        if (! $schema->hasTable($model->getTable())) {
            return "table {$model->getTable()} does not exist";
        }

        $plain = [$model->getKeyName(), $model->getCreatedAtColumn(), $model->getUpdatedAtColumn(), 'deleted_at'];

        if (array_diff($schema->getColumnListing($model->getTable()), $plain) === [] || in_array(LogsModuleActivity::class, class_uses_recursive($model), true)) {
            return null;
        }

        $optOut = (new ReflectionClass($model))->getAttributes(NotAudited::class)[0] ?? null;

        if ($optOut === null) {
            return 'has business columns but no LogsModuleActivity or #[NotAudited(reason: ...)]';
        }

        return trim($optOut->newInstance()->reason) === '' ? '#[NotAudited] needs a reason' : null;
    }

    /**
     * @return list<string>
     */
    private static function phpFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * The first argument of each `Audit::record(` in a file.
     *
     * @return list<array{int, 'case'|'string'|'other', string}> [line, kind, source text]
     */
    private static function recordCalls(string $file): array
    {
        $tokens = array_values(array_filter(
            token_get_all((string) file_get_contents($file)),
            fn (array|string $token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));
        $text = function (int $i) use ($tokens): string {
            $token = $tokens[$i] ?? '';

            return is_array($token) ? $token[1] : $token;
        };
        $calls = [];

        foreach ($tokens as $i => $token) {
            if (! is_array($token) || ! in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                || ($token[1] !== 'Audit' && ! str_ends_with($token[1], '\\Audit'))
                || $text($i + 1) !== '::' || $text($i + 2) !== 'record' || $text($i + 3) !== '(') {
                continue;
            }

            $arg = $text($i + 5) === ':' && $text($i + 4) === 'event' ? $i + 6 : $i + 4;
            $first = $text($arg);
            $kind = match (true) {
                ($first === 'AuditEvent' || str_ends_with($first, '\\AuditEvent')) && $text($arg + 1) === '::' => 'case',
                is_array($tokens[$arg] ?? null) && $tokens[$arg][0] === T_CONSTANT_ENCAPSED_STRING => 'string',
                default => 'other',
            };

            $calls[] = [$token[2], $kind, $kind === 'string' ? substr($first, 1, -1) : $first];
        }

        return $calls;
    }

    /**
     * @param  list<string>  $violations
     */
    private static function failOnViolations(string $rule, array $violations): void
    {
        Assert::assertSame([], $violations, $rule.":\n- ".implode("\n- ", $violations));
    }
}
