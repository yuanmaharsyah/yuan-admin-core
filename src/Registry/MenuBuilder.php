<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Yuan\AdminCore\Data\MenuGroupData;
use Yuan\AdminCore\Data\MenuItemData;

/**
 * The menu a user may see: groups in config order, items by `order` then label,
 * filtered by `{slug}.view` (or `admin-core.superadmin` for superadmin_only modules).
 */
final readonly class MenuBuilder
{
    public function __construct(
        private ModuleRegistry $registry,
        private Repository $config,
        private Gate $gate,
        private UrlGenerator $url,
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
                ), $modules),
            );
        }

        return $menu;
    }
}
