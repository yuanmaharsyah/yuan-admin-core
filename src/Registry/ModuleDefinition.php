<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use ReflectionClass;
use Yuan\AdminCore\Dashboard\Widget;

/**
 * A validated `module.php` (spec §3.1).
 *
 * @phpstan-type SubmenuLeaf array{label: string, route: string, action: string, badge: ?string, submenu: array{}}
 * @phpstan-type SubmenuItem array{label: string, route: ?string, action: string, badge: ?string, submenu: list<SubmenuLeaf>}
 */
final readonly class ModuleDefinition
{
    private const array KEYS = [
        'name', 'slug', 'label', 'icon', 'group', 'order', 'enabled',
        'core', 'superadmin_only', 'menu', 'actions', 'widgets',
    ];

    /** Actions with a fixed meaning and policy method (spec §6.2); any other declared action is custom. */
    public const array STANDARD_ACTIONS = ['view', 'create', 'edit', 'delete', 'download'];

    private const string KEBAB = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Longest menu badge, in characters (spec §3.1). */
    public const int BADGE_MAX = 24;

    /**
     * @param  list<string>  $actions
     * @param  list<class-string>  $widgets
     * @param  list<SubmenuItem>  $submenu  Children of the menu item (spec §3.1): a link (`route` relative to the module, `index` = `{slug}.index`) or a parent of links (`route` null, its own `submenu`).
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
        public ?string $badge = null,
        public array $submenu = [],
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

        $slug = self::string($config, 'slug', $file, self::KEBAB, 'must be lowercase kebab-case');
        $core = self::bool($config, 'core', $file);
        $enabled = self::bool($config, 'enabled', $file);
        $actions = self::actions($config['actions'], $file);

        if ($core && ! $enabled) {
            throw InvalidModuleDefinition::rule($file, 'enabled', 'cannot be false for a core module');
        }

        return new self(
            name: $name,
            slug: $slug,
            label: self::string($config, 'label', $file, '/\S/', 'must not be empty'),
            icon: self::icon($config['icon'], $file),
            group: $group,
            order: self::int($config, 'order', $file),
            enabled: $enabled,
            core: $core,
            superadminOnly: self::bool($config, 'superadmin_only', $file),
            menu: self::bool($config, 'menu', $file),
            actions: $actions,
            widgets: self::widgets($config['widgets'], $file, $slug),
            path: dirname($file),
            root: $root,
            namespace: $namespace,
            badge: self::badge($config['badge'] ?? null, $file, 'badge'),
            submenu: self::submenu($config['submenu'] ?? [], $file, $actions),
        );
    }

    public function providerClass(): string
    {
        return "{$this->namespace}{$this->name}\\Providers\\{$this->name}ServiceProvider";
    }

    /**
     * @return list<string>
     */
    public function customActions(): array
    {
        return array_values(array_diff($this->actions, self::STANDARD_ACTIONS));
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
     * A name from resources/icons/lucide.json, generated from the installed
     * lucide-react (`npm run icons`).
     */
    private static function icon(mixed $icon, string $file): string
    {
        /** @var list<string>|null $names */
        static $names = null;
        $names ??= json_decode((string) file_get_contents(dirname(__DIR__, 2).'/resources/icons/lucide.json'), true, flags: JSON_THROW_ON_ERROR);

        if (! is_string($icon) || ! in_array($icon, $names, true)) {
            throw InvalidModuleDefinition::rule($file, 'icon', 'must be a lucide icon name (resources/icons/lucide.json)');
        }

        return $icon;
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

    /** Optional short pill text next to a menu item (translation key allowed). */
    private static function badge(mixed $badge, string $file, string $key): ?string
    {
        if ($badge === null) {
            return null;
        }

        if (! is_string($badge) || trim($badge) === '' || mb_strlen($badge) > self::BADGE_MAX) {
            throw InvalidModuleDefinition::rule($file, $key, 'must be null or a short text (1 to '.self::BADGE_MAX.' characters)');
        }

        return $badge;
    }

    /**
     * The menu children (spec §3.1), two levels deep: a child is a link (a
     * relative `route`, its `action`) or, on the second level only, a parent
     * with its own `submenu` and no route.
     *
     * @param  list<string>  $actions
     * @return list<SubmenuItem>
     */
    private static function submenu(mixed $submenu, string $file, array $actions): array
    {
        $children = [];

        foreach (self::entries($submenu, $file, 'submenu') as $key => $child) {
            if (! array_key_exists('submenu', $child)) {
                $children[] = self::leaf($child, $file, $key, $actions);

                continue;
            }

            if (array_diff(array_keys($child), ['label', 'badge', 'submenu']) !== []) {
                throw InvalidModuleDefinition::rule($file, $key, 'with a "submenu" is only a parent: "label", "badge" and "submenu", no "route" or "action"');
            }

            $leaves = [];

            foreach (self::entries($child['submenu'], $file, "{$key}.submenu") as $path => $grandchild) {
                $leaves[] = self::leaf($grandchild, $file, $path, $actions);
            }

            if ($leaves === []) {
                throw InvalidModuleDefinition::rule($file, "{$key}.submenu", 'must not be empty');
            }

            $children[] = [
                'label' => self::label($child, $file, $key),
                'route' => null,
                'action' => 'view',
                'badge' => self::badge($child['badge'] ?? null, $file, "{$key}.badge"),
                'submenu' => $leaves,
            ];
        }

        return $children;
    }

    /**
     * @return array<string, array<mixed>> path => entry
     */
    private static function entries(mixed $list, string $file, string $path): array
    {
        if (! is_array($list) || ! array_is_list($list)) {
            throw InvalidModuleDefinition::rule($file, $path, 'must be a list');
        }

        $entries = [];

        foreach ($list as $index => $entry) {
            if (! is_array($entry)) {
                throw InvalidModuleDefinition::rule($file, "{$path}.{$index}", 'must be an array with "label" and "route"');
            }

            $entries["{$path}.{$index}"] = $entry;
        }

        return $entries;
    }

    /**
     * A link: label, relative route, action, badge. Links can't nest (three levels at most).
     *
     * @param  array<mixed>  $child
     * @param  list<string>  $actions
     * @return SubmenuLeaf
     */
    private static function leaf(array $child, string $file, string $key, array $actions): array
    {
        if (array_diff(array_keys($child), ['label', 'route', 'action', 'badge']) !== []) {
            throw InvalidModuleDefinition::rule($file, $key, 'may only have "label", "route", "action" and "badge" (a "submenu" only on the second level: three levels at most)');
        }

        $label = self::label($child, $file, $key);

        if (! is_string($child['route'] ?? null) || preg_match('/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/', $child['route']) !== 1) {
            throw InvalidModuleDefinition::rule($file, "{$key}.route", 'must be a route name relative to the module (e.g. "index")');
        }

        $action = $child['action'] ?? 'view';

        if (! is_string($action) || ! in_array($action, $actions, true)) {
            throw InvalidModuleDefinition::rule($file, "{$key}.action", 'must be one of the module\'s actions ('.implode(', ', $actions).')');
        }

        return ['label' => $label, 'route' => $child['route'], 'action' => $action, 'badge' => self::badge($child['badge'] ?? null, $file, "{$key}.badge"), 'submenu' => []];
    }

    /**
     * @param  array<mixed>  $child
     */
    private static function label(array $child, string $file, string $key): string
    {
        if (! is_string($child['label'] ?? null) || trim($child['label']) === '') {
            throw InvalidModuleDefinition::rule($file, "{$key}.label", 'must not be empty');
        }

        return $child['label'];
    }

    /**
     * @return list<class-string>
     */
    private static function widgets(mixed $widgets, string $file, string $slug): array
    {
        if (! is_array($widgets) || ! array_is_list($widgets)) {
            throw InvalidModuleDefinition::rule($file, 'widgets', 'must be a list');
        }

        $classes = [];

        foreach ($widgets as $widget) {
            if (! is_string($widget) || ! class_exists($widget)) {
                throw InvalidModuleDefinition::rule($file, 'widgets', 'must only list existing classes');
            }

            if (! is_subclass_of($widget, Widget::class)) {
                throw InvalidModuleDefinition::rule($file, 'widgets', "must only list Widget classes ({$widget} isn't one)");
            }

            // key() is a constant per class; no constructor runs at boot.
            $key = (new ReflectionClass($widget))->newInstanceWithoutConstructor()->key();

            if (! str_starts_with($key, "{$slug}.")) {
                throw InvalidModuleDefinition::rule($file, 'widgets', "must have keys starting with \"{$slug}.\" ({$widget} has \"{$key}\")");
            }

            $classes[] = $widget;
        }

        return $classes;
    }
}
