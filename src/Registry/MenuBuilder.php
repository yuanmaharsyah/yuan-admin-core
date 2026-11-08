<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Yuan\AdminCore\Data\MenuChildData;
use Yuan\AdminCore\Data\MenuGroupData;
use Yuan\AdminCore\Data\MenuItemData;

/**
 * The menu a user may see: groups in config order, items by `order` then label,
 * filtered by `{slug}.view` (or `admin-core.superadmin` for superadmin_only modules).
 * `create_href` feeds the command palette's "Aksi" section. Submenu children
 * are filtered by their own action (`{slug}.{action}`).
 */
final readonly class MenuBuilder
{
    public function __construct(
        private ModuleRegistry $registry,
        private Repository $config,
        private Gate $gate,
        private UrlGenerator $url,
        private Router $router,
    ) {}

    /**
     * @return list<MenuGroupData>
     */
    public function build(Authenticatable $user): array
    {
        $gate = $this->gate->forUser($user);
        $byGroup = [];

        foreach ($this->registry->enabled() as $module) {
            $ability = $module->superadminOnly ? 'admin-core.superadmin' : "{$module->slug}.view";

            if ($module->menu && $gate->allows($ability)) {
                $byGroup[$module->group][] = $module;
            }
        }

        /** @var array<string, array{label: string, icon: string, order: int}> $groups */
        $groups = $this->config->get('admin-core.menu.groups', []);
        uasort($groups, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        $menu = [];

        foreach ($groups as $key => $group) {
            if (! isset($byGroup[$key])) {
                continue;
            }

            $modules = $byGroup[$key];
            usort($modules, fn (ModuleDefinition $a, ModuleDefinition $b): int => [$a->order, $a->label] <=> [$b->order, $b->label]);

            $menu[] = new MenuGroupData(
                key: $key,
                label: __($group['label']),
                icon: $group['icon'],
                items: array_map(fn (ModuleDefinition $module): MenuItemData => new MenuItemData(
                    slug: $module->slug,
                    label: __($module->label),
                    icon: $module->icon,
                    href: $this->url->route("{$module->slug}.index"),
                    create_href: $this->router->has("{$module->slug}.create") && $gate->allows("{$module->slug}.create")
                        ? $this->url->route("{$module->slug}.create")
                        : null,
                    badge: $this->translated($module->badge),
                    children: $this->children($module, $module->submenu, $gate),
                ), $modules),
            );
        }

        return $menu;
    }

    /**
     * The children the user may open; a parent child (third level) only when
     * at least one of its links is left.
     *
     * @param  list<array{label: string, route: ?string, action: string, badge: ?string, submenu: list<array{label: string, route: string, action: string, badge: ?string, submenu: array{}}>}>|list<array{label: string, route: string, action: string, badge: ?string, submenu: array{}}>  $entries
     * @return list<MenuChildData>
     */
    private function children(ModuleDefinition $module, array $entries, Gate $gate): array
    {
        $children = [];

        foreach ($entries as $entry) {
            if ($entry['route'] === null) {
                $links = $this->children($module, $entry['submenu'], $gate);

                if ($links !== []) {
                    $children[] = new MenuChildData(label: __($entry['label']), href: null, route: null, badge: $this->translated($entry['badge']), children: $links);
                }

                continue;
            }

            $ability = $module->superadminOnly ? 'admin-core.superadmin' : "{$module->slug}.{$entry['action']}";

            if (! $gate->allows($ability)) {
                continue;
            }

            $route = "{$module->slug}.{$entry['route']}";
            $children[] = new MenuChildData(
                label: __($entry['label']),
                href: $this->url->route($route),
                route: $route,
                badge: $this->translated($entry['badge']),
            );
        }

        return $children;
    }

    private function translated(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $translated = __($text);

        return is_string($translated) ? $translated : $text;
    }
}
