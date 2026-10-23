import { Link } from '@inertiajs/react';
import { LogOut, PanelLeftClose, PanelLeftOpen } from 'lucide-react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';
import { navigatesDirectly } from '../../lib/shell';
import { cn } from '../../lib/utils';
import { Tooltip, TooltipContent, TooltipTrigger } from '../ui/tooltip';
import { Icon } from './Icon';

type Props = {
    menu: Yuan.AdminCore.Data.MenuGroupData[];
    appName: string;
    activeKey?: string;
    onSelectGroup: (key: string) => void;
    /** A one-item group's link was followed (it may be the current URL). */
    onNavigate: () => void;
    logout: Href;
    /** Panel toggle; omitted in the mobile sheet. */
    panel?: { open: boolean; onToggle: () => void };
    /** 44px touch targets in the mobile sheet, 40px on desktop. */
    touch?: boolean;
};

/** The group rail (56px): logo, one icon per group, panel toggle and Keluar. */
export function AppRail({
    menu,
    appName,
    activeKey,
    onSelectGroup,
    onNavigate,
    logout,
    panel,
    touch = false,
}: Props) {
    const t = useT();
    const size = touch ? 'size-row' : 'size-10';
    const buttonClass = (active: boolean) =>
        cn(
            'flex shrink-0 items-center justify-center rounded',
            size,
            active
                ? 'bg-highlight text-highlight-foreground'
                : 'text-rail-foreground hover:bg-sidebar-active',
        );

    return (
        <nav
            aria-label={t('shell.rail')}
            className="flex h-full w-rail flex-col items-center gap-2 bg-rail py-3 text-rail-foreground"
        >
            <div
                aria-hidden="true"
                className="flex size-8 shrink-0 items-center justify-center rounded bg-primary text-base font-bold text-card"
            >
                {appName.charAt(0).toUpperCase()}
            </div>
            <div className="my-1.5 h-px w-8 shrink-0 bg-border" />
            <div className="flex min-h-0 flex-1 flex-col items-center gap-2 overflow-y-auto">
                {menu.map((group) => {
                    const active = group.key === activeKey;
                    const icon = <Icon name={group.icon} className="size-5" />;
                    const control =
                        navigatesDirectly(group) && group.items[0] ? (
                            <Link
                                href={group.items[0].href}
                                aria-label={group.label}
                                aria-current={active ? 'true' : undefined}
                                onClick={onNavigate}
                                className={buttonClass(active)}
                            >
                                {icon}
                            </Link>
                        ) : (
                            <button
                                type="button"
                                aria-label={group.label}
                                aria-current={active ? 'true' : undefined}
                                onClick={() => onSelectGroup(group.key)}
                                className={buttonClass(active)}
                            >
                                {icon}
                            </button>
                        );

                    return (
                        <Tooltip key={group.key}>
                            <TooltipTrigger asChild>{control}</TooltipTrigger>
                            <TooltipContent side="right">
                                {group.label}
                            </TooltipContent>
                        </Tooltip>
                    );
                })}
            </div>
            {panel && (
                <button
                    type="button"
                    aria-label={t(
                        panel.open
                            ? 'shell.panel_collapse'
                            : 'shell.panel_expand',
                    )}
                    aria-expanded={panel.open}
                    onClick={panel.onToggle}
                    className={buttonClass(false)}
                >
                    {panel.open ? (
                        <PanelLeftClose className="size-5" />
                    ) : (
                        <PanelLeftOpen className="size-5" />
                    )}
                </button>
            )}
            <Link
                href={logout}
                as="button"
                aria-label={t('shell.logout')}
                className={buttonClass(false)}
            >
                <LogOut className="size-5" />
            </Link>
        </nav>
    );
}
