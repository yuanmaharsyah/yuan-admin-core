import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { useId, useState } from 'react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';
import { initials } from '../../lib/shell';
import { cn } from '../../lib/utils';
import { Badge } from '../ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '../ui/tooltip';
import { AppMark } from './AppMark';
import { Icon } from './Icon';

type Props = {
    menu: Yuan.AdminCore.Data.MenuGroupData[];
    appName: string;
    logo: string | null;
    activeSlug?: string;
    /** The current route name (`app.route`): marks the active submenu child. */
    activeRoute?: string | null;
    auth: Yuan.AdminCore.Data.AuthData;
    profile: Href;
    /** Icon-only strip (desktop, `Ctrl+B`); never in the mobile sheet. */
    collapsed?: boolean;
    /** Extra control next to the app name (the sheet's close button). */
    action?: ReactNode;
    /** 44px touch targets in the mobile sheet, 36px on desktop. */
    touch?: boolean;
};

/** Shows the label in a tooltip only while the sidebar is an icon strip. */
function WithTooltip({
    label,
    show,
    children,
}: {
    label: string;
    show: boolean;
    children: ReactNode;
}) {
    if (!show) {
        return children;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>{children}</TooltipTrigger>
            <TooltipContent side="right">{label}</TooltipContent>
        </Tooltip>
    );
}

/**
 * The sidebar (ui-design §2, frontend-behavior §3): app mark and name, every
 * group as a labelled section with its items, the current user at the bottom.
 * Collapsed, it is a 56px icon strip with tooltips.
 */
export function AppSidebar({
    menu,
    appName,
    logo,
    activeSlug,
    activeRoute = null,
    auth,
    profile,
    collapsed = false,
    action,
    touch = false,
}: Props) {
    const t = useT();

    return (
        <nav
            aria-label={t('shell.navigation')}
            data-collapsed={collapsed || undefined}
            className={cn(
                'flex h-full min-w-0 flex-col bg-sidebar text-sidebar-foreground transition-[width] duration-200 ease-linear',
                collapsed ? 'w-sidebar-icon' : 'w-sidebar',
            )}
        >
            <div
                className={cn(
                    'flex h-topbar shrink-0 items-center gap-2.5',
                    collapsed ? 'justify-center' : 'px-4',
                )}
            >
                <AppMark name={appName} logo={logo} />
                {!collapsed && (
                    <span className="min-w-0 flex-1 truncate text-brand">
                        {appName}
                    </span>
                )}
                {action}
            </div>

            <div
                className={cn(
                    'flex min-h-0 flex-1 flex-col overflow-x-hidden overflow-y-auto pb-3',
                    collapsed ? 'items-center px-2' : 'px-2',
                )}
            >
                {menu.map((group) => (
                    <div
                        key={group.key}
                        role="group"
                        aria-label={group.label}
                        className="flex w-full flex-col gap-0.5 pt-4 first:pt-2"
                    >
                        {collapsed ? (
                            <div
                                aria-hidden="true"
                                className="mx-auto mb-1.5 h-px w-6 bg-sidebar-border"
                            />
                        ) : (
                            <div className="px-3 pb-1.5 text-label text-muted-foreground uppercase">
                                {group.label}
                            </div>
                        )}
                        {group.items.map((item) => (
                            <SidebarItem
                                key={item.slug}
                                item={item}
                                active={item.slug === activeSlug}
                                activeRoute={activeRoute}
                                collapsed={collapsed}
                                touch={touch}
                            />
                        ))}
                    </div>
                ))}
            </div>

            {auth.user && (
                <div className="shrink-0 border-t border-sidebar-border p-2">
                    <WithTooltip label={auth.user.name} show={collapsed}>
                        <Link
                            href={profile}
                            aria-label={collapsed ? auth.user.name : undefined}
                            className={cn(
                                'flex items-center gap-2.5 rounded-md p-2 transition-colors duration-200 hover:bg-sidebar-accent',
                                collapsed && 'justify-center',
                            )}
                        >
                            <span
                                aria-hidden="true"
                                className="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-sidebar-accent text-xs font-semibold"
                            >
                                {auth.user.avatar_url ? (
                                    <img
                                        src={auth.user.avatar_url}
                                        alt=""
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    initials(auth.user.name)
                                )}
                            </span>
                            {!collapsed && (
                                <span className="flex min-w-0 flex-col">
                                    <span className="truncate text-table font-medium">
                                        {auth.user.name}
                                    </span>
                                    {auth.level && (
                                        <span className="truncate text-xs text-muted-foreground">
                                            {auth.level.label}
                                        </span>
                                    )}
                                </span>
                            )}
                        </Link>
                    </WithTooltip>
                </div>
            )}
        </nav>
    );
}

type ItemProps = {
    item: Yuan.AdminCore.Data.MenuItemData;
    /** The current route belongs to this item's module. */
    active: boolean;
    activeRoute: string | null;
    collapsed: boolean;
    touch: boolean;
};

const itemClass = (touch: boolean, collapsed: boolean) =>
    cn(
        'flex w-full shrink-0 items-center gap-3 rounded-md text-body font-medium transition-colors duration-200',
        touch ? 'h-row' : 'h-control',
        collapsed ? 'mx-auto size-control justify-center' : 'px-3',
    );

const idleClass =
    'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground';

/**
 * One menu item: a link, or (with `children`) a disclosure whose children sit
 * under a guide line; the parent of the current route starts open. Collapsed,
 * a parent opens its children in a menu to the right.
 */
function SidebarItem({
    item,
    active,
    activeRoute,
    collapsed,
    touch,
}: ItemProps) {
    const [open, setOpen] = useState(active);
    const listId = useId();
    const icon = (
        <Icon name={item.icon} aria-hidden="true" className="size-4 shrink-0" />
    );

    if (item.children.length === 0) {
        return (
            <WithTooltip label={item.label} show={collapsed}>
                <Link
                    href={item.href}
                    aria-label={collapsed ? item.label : undefined}
                    aria-current={active ? 'page' : undefined}
                    className={cn(
                        itemClass(touch, collapsed),
                        active
                            ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                            : idleClass,
                    )}
                >
                    {icon}
                    {!collapsed && (
                        <>
                            <span className="flex-1 truncate">
                                {item.label}
                            </span>
                            {item.badge && (
                                <Badge
                                    variant={active ? 'secondary' : 'default'}
                                >
                                    {item.badge}
                                </Badge>
                            )}
                        </>
                    )}
                </Link>
            </WithTooltip>
        );
    }

    if (collapsed) {
        return (
            <DropdownMenu>
                <WithTooltip label={item.label} show>
                    <DropdownMenuTrigger
                        aria-label={item.label}
                        className={cn(
                            itemClass(touch, true),
                            active
                                ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                                : idleClass,
                        )}
                    >
                        {icon}
                    </DropdownMenuTrigger>
                </WithTooltip>
                <DropdownMenuContent
                    side="right"
                    align="start"
                    className="w-56"
                >
                    <DropdownMenuLabel className="text-label text-muted-foreground uppercase">
                        {item.label}
                    </DropdownMenuLabel>
                    {item.children.map((child) =>
                        child.children.length > 0 ? (
                            <DropdownMenuSub key={child.label}>
                                <DropdownMenuSubTrigger>
                                    <span className="flex-1 truncate">
                                        {child.label}
                                    </span>
                                    {child.badge && (
                                        <Badge>{child.badge}</Badge>
                                    )}
                                </DropdownMenuSubTrigger>
                                <DropdownMenuSubContent className="w-52">
                                    {child.children.map((link) => (
                                        <FlyoutLink
                                            key={link.route ?? link.label}
                                            link={link}
                                            activeRoute={activeRoute}
                                        />
                                    ))}
                                </DropdownMenuSubContent>
                            </DropdownMenuSub>
                        ) : (
                            <FlyoutLink
                                key={child.route ?? child.label}
                                link={child}
                                activeRoute={activeRoute}
                            />
                        ),
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
        );
    }

    return (
        <div className="flex flex-col">
            <button
                type="button"
                aria-expanded={open}
                aria-controls={listId}
                onClick={() => setOpen(!open)}
                className={cn(
                    itemClass(touch, false),
                    idleClass,
                    active && 'text-foreground',
                )}
            >
                {icon}
                <span className="flex-1 truncate text-left">{item.label}</span>
                {item.badge && <Badge>{item.badge}</Badge>}
                <ChevronRight
                    aria-hidden="true"
                    className={cn(
                        'size-4 shrink-0 text-muted-foreground transition-transform duration-200',
                        open && 'rotate-90',
                    )}
                />
            </button>
            <ChildList
                id={listId}
                open={open}
                items={item.children}
                activeRoute={activeRoute}
                touch={touch}
            />
        </div>
    );
}

type Child = Yuan.AdminCore.Data.MenuChildData;

/** The child links to the current route, or holds the one that does. */
function holdsRoute(child: Child, route: string | null): boolean {
    return (
        child.route === route ||
        child.children.some((nested) => holdsRoute(nested, route))
    );
}

/** A disclosure's children under a guide line; the height animates open and closed. */
function ChildList({
    id,
    open,
    items,
    activeRoute,
    touch,
}: {
    id: string;
    open: boolean;
    items: Child[];
    activeRoute: string | null;
    touch: boolean;
}) {
    return (
        <div
            id={id}
            inert={!open}
            className={cn(
                'grid transition-[grid-template-rows] duration-200 ease-out',
                open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
            )}
        >
            <ul className="m-0 ml-5 flex min-h-0 list-none flex-col gap-0.5 overflow-hidden border-l border-sidebar-border p-0 pl-2">
                {items.map((child, index) => (
                    <li
                        key={child.route ?? child.label}
                        className={cn(index === 0 && 'mt-0.5')}
                    >
                        <ChildEntry
                            child={child}
                            activeRoute={activeRoute}
                            touch={touch}
                        />
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** A second- or third-level entry: a link, or a parent of links (the third level). */
function ChildEntry({
    child,
    activeRoute,
    touch,
}: {
    child: Child;
    activeRoute: string | null;
    touch: boolean;
}) {
    const [open, setOpen] = useState(holdsRoute(child, activeRoute));
    const listId = useId();
    const rowClass = cn(
        'flex w-full items-center gap-2 rounded-md px-3 text-body transition-colors duration-200',
        touch ? 'h-row' : 'h-control',
    );
    const badge = child.badge && <Badge>{child.badge}</Badge>;

    if (child.href === null) {
        return (
            <>
                <button
                    type="button"
                    aria-expanded={open}
                    aria-controls={listId}
                    onClick={() => setOpen(!open)}
                    className={cn(
                        rowClass,
                        idleClass,
                        holdsRoute(child, activeRoute)
                            ? 'text-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    <span className="flex-1 truncate text-left">
                        {child.label}
                    </span>
                    {badge}
                    <ChevronRight
                        aria-hidden="true"
                        className={cn(
                            'size-4 shrink-0 transition-transform duration-200',
                            open && 'rotate-90',
                        )}
                    />
                </button>
                <ChildList
                    id={listId}
                    open={open}
                    items={child.children}
                    activeRoute={activeRoute}
                    touch={touch}
                />
            </>
        );
    }

    const current = child.route === activeRoute;

    return (
        <Link
            href={child.href}
            aria-current={current ? 'page' : undefined}
            className={cn(
                rowClass,
                current
                    ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                    : cn(idleClass, 'text-muted-foreground'),
            )}
        >
            <span className="flex-1 truncate">{child.label}</span>
            {badge}
        </Link>
    );
}

/** One link in the collapsed sidebar's flyout. */
function FlyoutLink({
    link,
    activeRoute,
}: {
    link: Child;
    activeRoute: string | null;
}) {
    return (
        <DropdownMenuItem asChild>
            <Link
                href={link.href ?? ''}
                aria-current={link.route === activeRoute ? 'page' : undefined}
                className="w-full aria-[current=page]:font-medium"
            >
                <span className="flex-1 truncate">{link.label}</span>
                {link.badge && <Badge>{link.badge}</Badge>}
            </Link>
        </DropdownMenuItem>
    );
}
