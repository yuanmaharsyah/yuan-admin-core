import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';
import { initials } from '../../lib/shell';
import { cn } from '../../lib/utils';
import { Icon } from './Icon';

type Props = {
    appName: string;
    group?: Yuan.AdminCore.Data.MenuGroupData;
    activeSlug?: string;
    auth: Yuan.AdminCore.Data.AuthData;
    profile: Href;
    /** Extra control next to the app name (the sheet's close button). */
    action?: ReactNode;
    touch?: boolean;
};

/** The item panel (220px): app name, the viewed group's items, the current user. */
export function ItemPanel({
    appName,
    group,
    activeSlug,
    auth,
    profile,
    action,
    touch = false,
}: Props) {
    const t = useT();

    return (
        <nav
            aria-label={t('shell.panel', { group: group?.label ?? appName })}
            className="flex h-full w-panel min-w-0 flex-col gap-1 bg-sidebar px-3 pt-5 pb-3 text-sidebar-foreground"
        >
            <div className="flex items-start gap-2 px-2 pb-4">
                <div className="min-w-0 flex-1 truncate text-brand text-sidebar-active-foreground">
                    {appName}
                </div>
                {action}
            </div>
            {group && (
                <div className="px-2 pb-1.5 text-label text-muted-foreground uppercase">
                    {group.label}
                </div>
            )}
            {group?.items.map((item) => {
                const active = item.slug === activeSlug;

                return (
                    <Link
                        key={item.slug}
                        href={item.href}
                        aria-current={active ? 'page' : undefined}
                        className={cn(
                            'relative flex shrink-0 items-center gap-2.5 rounded px-2.5 text-body',
                            touch ? 'h-row' : 'h-control',
                            active
                                ? 'bg-sidebar-active font-semibold text-sidebar-active-foreground'
                                : 'hover:bg-sidebar-active hover:text-sidebar-active-foreground',
                        )}
                    >
                        {active && (
                            <span
                                aria-hidden="true"
                                className="absolute inset-y-1.5 left-0 w-0.75 bg-primary"
                            />
                        )}
                        <Icon name={item.icon} className="size-4" />
                        <span className="truncate">{item.label}</span>
                    </Link>
                );
            })}
            <div className="flex-1" />
            {auth.user && (
                <Link
                    href={profile}
                    className="flex items-center gap-2.5 border-t border-sidebar-border px-2 py-2.5 text-sidebar-active-foreground"
                >
                    <span
                        aria-hidden="true"
                        className="flex size-7 shrink-0 items-center justify-center rounded-full bg-card text-label"
                    >
                        {initials(auth.user.name)}
                    </span>
                    <span className="flex min-w-0 flex-col">
                        <span className="truncate text-table font-semibold">
                            {auth.user.name}
                        </span>
                        {auth.level && (
                            <span className="truncate text-xs text-muted-foreground">
                                {auth.level.label}
                            </span>
                        )}
                    </span>
                </Link>
            )}
        </nav>
    );
}
