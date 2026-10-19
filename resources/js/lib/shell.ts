import type { InertiaLinkProps } from '@inertiajs/react';

export type Href = NonNullable<InertiaLinkProps['href']>;

/** Project links the shell needs; the project passes them from its Wayfinder routes. */
export type ShellLinks = {
    profile: Href;
    logout: Href;
};

type MenuGroup = Yuan.AdminCore.Data.MenuGroupData;
type MenuItem = Yuan.AdminCore.Data.MenuItemData;

/** The menu item whose `{slug}.` prefixes the current route name, and its group. */
export function activeMenu(
    menu: MenuGroup[],
    route: string | null,
): { group?: MenuGroup; item?: MenuItem } {
    for (const group of menu) {
        const item = group.items.find((candidate) =>
            route?.startsWith(`${candidate.slug}.`),
        );

        if (item) {
            return { group, item };
        }
    }

    return {};
}

/** A group navigates directly when it has one item, or is Beranda. */
export function navigatesDirectly(group: MenuGroup): boolean {
    return group.key === 'beranda' || group.items.length === 1;
}

export function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}
