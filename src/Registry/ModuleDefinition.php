<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

/**
 * A validated `module.php` (spec §3.1).
 */
final readonly class ModuleDefinition
{
    private const array KEYS = [
        'name', 'slug', 'label', 'icon', 'group', 'order', 'enabled',
        'core', 'superadmin_only', 'menu', 'actions', 'widgets',
    ];

    private const string KEBAB = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param  list<string>  $actions
     * @param  list<class-string>  $widgets
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $label,
        public string $icon,
        public string $group,
        public int $order,
        public bool $enabled,
        public bool $core,
        public bool $superadminOnly,
        public bool $menu,
        public array $actions,
        public array $widgets,
        public string $path,
        public string $root,
        public string $namespace,
    ) {}

    /**
     * @param  list<string>  $groups  Configured menu group keys.
     */
    public static function fromConfig(mixed $config, string $file, string $root, string $namespace, array $groups): self
    {
        if (! is_array($config)) {
            throw InvalidModuleDefinition::rule($file, 'module.php', 'must return an array');
        }

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $config)) {
                throw InvalidModuleDefinition::rule($file, $key, 'is missing');
            }
        }

        $name = self::string($config, 'name', $file, '/^[A-Z][A-Za-z0-9]*$/', 'must be PascalCase');
        $folder = basename(dirname($file));

        if ($name !== $folder) {
            throw InvalidModuleDefinition::rule($file, 'name', "must equal the folder name \"{$folder}\"");
        }

        $group = self::string($config, 'group', $file, self::KEBAB, 'must be a menu group key');

        if (! in_array($group, $groups, true)) {
            throw InvalidModuleDefinition::rule($file, 'group', 'must be one of the configured menu groups ('.implode(', ', $groups).')');
        }

        $core = self::bool($config, 'core', $file);
        $enabled = self::bool($config, 'enabled', $file);

        if ($core && ! $enabled) {
            throw InvalidModuleDefinition::rule($file, 'enabled', 'cannot be false for a core module');
        }

        return new self(
            name: $name,
            slug: self::string($config, 'slug', $file, self::KEBAB, 'must be lowercase kebab-case'),
            label: self::string($config, 'label', $file, '/\S/', 'must not be empty'),
            icon: self::string($config, 'icon', $file, self::KEBAB, 'must be a lowercase kebab-case lucide icon name'),
            group: $group,
            order: self::int($config, 'order', $file),
            enabled: $enabled,
            core: $core,
            superadminOnly: self::bool($config, 'superadmin_only', $file),
            menu: self::bool($config, 'menu', $file),
            actions: self::actions($config['actions'], $file),
            widgets: self::widgets($config['widgets'], $file),
            path: dirname($file),
            root: $root,
            namespace: $namespace,
        );
    }

    public function providerClass(): string
    {
        return "{$this->namespace}{$this->name}\\Providers\\{$this->name}ServiceProvider";
    }

    public function path(string $relative): string
    {
        return $this->path.'/'.$relative;
    }

    /**
     * @param  array<mixed>  $config
     */
    private static function string(array $config, string $key, string $file, string $pattern, string $rule): string
    {
        $value = $config[$key];

        if (! is_string($value) || preg_match($pattern, $value) !== 1) {
            throw InvalidModuleDefinition::rule($file, $key, $rule);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $config
     */
    private static function bool(array $config, string $key, string $file): bool
    {
        if (! is_bool($config[$key])) {
            throw InvalidModuleDefinition::rule($file, $key, 'must be true or false');
        }

        return $config[$key];
    }

    /**
     * @param  array<mixed>  $config
     */
    private static function int(array $config, string $key, string $file): int
    {
        if (! is_int($config[$key])) {
            throw InvalidModuleDefinition::rule($file, $key, 'must be an integer');
        }

        return $config[$key];
    }

    /**
     * @return list<string>
     */
    private static function actions(mixed $actions, string $file): array
    {
        if (! is_array($actions) || ! array_is_list($actions)) {
            throw InvalidModuleDefinition::rule($file, 'actions', 'must be a list');
        }

        $list = [];

        foreach ($actions as $action) {
            if (! is_string($action) || preg_match('/^[a-z]+$/', $action) !== 1) {
                throw InvalidModuleDefinition::rule($file, 'actions', 'may only contain single lowercase words');
            }

            if (in_array($action, $list, true)) {
                throw InvalidModuleDefinition::rule($file, 'actions', "must not repeat \"{$action}\"");
            }

            $list[] = $action;
        }

        if (! in_array('view', $list, true)) {
            throw InvalidModuleDefinition::rule($file, 'actions', 'must include "view"');
        }

        return $list;
    }

    /**
     * @return list<class-string>
     */
    private static function widgets(mixed $widgets, string $file): array
    {
        if (! is_array($widgets) || ! array_is_list($widgets)) {
            throw InvalidModuleDefinition::rule($file, 'widgets', 'must be a list');
        }

        $classes = [];

        foreach ($widgets as $widget) {
            if (! is_string($widget) || ! class_exists($widget)) {
                throw InvalidModuleDefinition::rule($file, 'widgets', 'must only list existing classes');
            }

            $classes[] = $widget;
        }

        return $classes;
    }
}
