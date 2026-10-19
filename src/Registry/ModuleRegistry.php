<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use Illuminate\Contracts\Config\Repository;

/**
 * Discovers and validates the modules of every configured root (spec §3).
 */
final class ModuleRegistry
{
    public const string CORE_ROOT = 'core';

    /** @var list<ModuleDefinition>|null */
    private ?array $modules = null;

    /**
     * @param  list<array{name: string, path: string, namespace: string}>  $roots
     * @param  list<string>  $groups
     * @param  list<string>  $disabled
     */
    public function __construct(
        private readonly array $roots,
        private readonly array $groups,
        private readonly array $disabled,
        private readonly string $cachePath,
    ) {}

    public static function fromConfig(Repository $config): self
    {
        /** @var list<array{name: string, path: string, namespace: string}> $roots */
        $roots = $config->get('admin-core.modules.roots', []);
        /** @var array<string, mixed> $groups */
        $groups = $config->get('admin-core.menu.groups', []);
        /** @var list<string> $disabled */
        $disabled = $config->get('admin-core.modules.disabled', []);

        return new self($roots, array_keys($groups), $disabled, $config->string('admin-core.modules.cache_path'));
    }

    /**
     * Every module present on disk, enabled or not.
     *
     * @return list<ModuleDefinition>
     */
    public function all(): array
    {
        return $this->modules ??= $this->build($this->isCached() ? $this->cachedEntries() : $this->scan());
    }

    /**
     * Modules whose routes, menu items and permissions are active.
     *
     * @return list<ModuleDefinition>
     */
    public function enabled(): array
    {
        return array_values(array_filter($this->all(), $this->isEnabled(...)));
    }

    public function isEnabled(ModuleDefinition $module): bool
    {
        return $module->enabled && ! $this->isTurnedOff($module);
    }

    public function find(string $slug): ?ModuleDefinition
    {
        foreach ($this->all() as $module) {
            if ($module->slug === $slug) {
                return $module;
            }
        }

        return null;
    }

    /**
     * The module a class belongs to, from its namespace (`{root namespace}{Name}\\`).
     */
    public function forClass(string $class): ?ModuleDefinition
    {
        foreach ($this->all() as $module) {
            if (str_starts_with($class, $module->namespace.$module->name.'\\')) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Raw `module.php` contents of every root, read from disk.
     *
     * @return list<array{file: string, root: string, namespace: string, config: mixed}>
     */
    public function scan(): array
    {
        $entries = [];

        foreach ($this->roots as $root) {
            foreach (glob($root['path'].'/*/module.php') ?: [] as $file) {
                $entries[] = [
                    'file' => $file,
                    'root' => $root['name'],
                    'namespace' => $root['namespace'],
                    'config' => require $file,
                ];
            }
        }

        return $entries;
    }

    /**
     * Validate what is on disk and write it to the cache file.
     */
    public function cache(): void
    {
        $entries = $this->scan();
        $this->build($entries);

        file_put_contents($this->cachePath, '<?php return '.var_export($entries, true).';'.PHP_EOL);
        $this->modules = null;
    }

    public function clearCache(): void
    {
        if ($this->isCached()) {
            unlink($this->cachePath);
        }

        $this->modules = null;
    }

    public function isCached(): bool
    {
        return is_file($this->cachePath);
    }

    /**
     * @return list<array{file: string, root: string, namespace: string, config: mixed}>
     */
    public function cachedEntries(): array
    {
        /** @var list<array{file: string, root: string, namespace: string, config: mixed}> */
        return require $this->cachePath;
    }

    /**
     * @param  list<array{file: string, root: string, namespace: string, config: mixed}>  $entries
     * @return list<ModuleDefinition>
     */
    private function build(array $entries): array
    {
        $modules = [];
        $slugs = [];
        $names = [];

        foreach ($entries as $entry) {
            $module = ModuleDefinition::fromConfig($entry['config'], $entry['file'], $entry['root'], $entry['namespace'], $this->groups);
            $turnedOff = $this->isTurnedOff($module);

            if ($turnedOff && $module->core) {
                throw InvalidModuleDefinition::coreDisabled($module->slug, $entry['file']);
            }

            if (! $turnedOff) {
                if (isset($slugs[$module->slug])) {
                    throw InvalidModuleDefinition::duplicate('slug', $module->slug, $slugs[$module->slug], $entry['file']);
                }

                if (isset($names[$module->name])) {
                    throw InvalidModuleDefinition::duplicate('name', $module->name, $names[$module->name], $entry['file']);
                }

                $slugs[$module->slug] = $entry['file'];
                $names[$module->name] = $entry['file'];
            }

            $modules[] = $module;
        }

        return $modules;
    }

    /**
     * `admin-core.modules.disabled` only applies to the core root, so a project
     * module can take over the slug of a core module it replaces.
     */
    private function isTurnedOff(ModuleDefinition $module): bool
    {
        return $module->root === self::CORE_ROOT && in_array($module->slug, $this->disabled, true);
    }
}
