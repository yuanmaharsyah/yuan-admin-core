import { router } from '@inertiajs/react';
import { Command } from 'cmdk';
import { History, Plus, Search } from 'lucide-react';
import type { ReactNode } from 'react';
import { usePermission } from '../../hooks/usePermission';
import { readRecentPages } from '../../hooks/useRecentPages';
import { useT } from '../../lib/i18n';
import { Dialog, DialogContent, DialogTitle } from '../ui/dialog';
import { Icon } from './Icon';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    menu: Yuan.AdminCore.Data.MenuGroupData[];
};

/** Path of a URL, absolute or relative. */
const pathOf = (url: string): string =>
    new URL(url, window.location.origin).pathname;

/**
 * Ctrl/Cmd+K palette (frontend-behavior §4): Navigasi (the menu), Aksi
 * (create routes the user may use), Terakhir dibuka (last 5 admin pages still
 * in the menu). Fuzzy search on label + group.
 */
export function CommandPalette({ open, onOpenChange, menu }: Props) {
    const t = useT();
    const { can } = usePermission();
    const items = menu.flatMap((group) =>
        group.items.map((item) => ({ ...item, group: group.label })),
    );
    const actions = items.filter(
        (item) => item.create_href !== null && can(`${item.slug}.create`),
    );
    const menuPaths = items.map((item) => pathOf(item.href));
    const recent = open
        ? readRecentPages().filter((page) =>
              menuPaths.some(
                  (path) =>
                      pathOf(page.url) === path ||
                      pathOf(page.url).startsWith(`${path}/`),
              ),
          )
        : [];
    const go = (url: string) => {
        onOpenChange(false);
        router.visit(url);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className="top-[15%] max-w-140 translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-140 [&>button:last-child]:hidden"
                aria-describedby={undefined}
            >
                <DialogTitle className="sr-only">
                    {t('palette.title')}
                </DialogTitle>
                <Command label={t('palette.title')} loop>
                    <div className="flex h-topbar items-center gap-2.5 border-b border-border px-3.5">
                        <Search
                            aria-hidden="true"
                            className="size-4 text-muted-foreground"
                        />
                        <Command.Input
                            aria-label={t('palette.input_label')}
                            placeholder={t('palette.input')}
                            className="h-full flex-1 bg-transparent text-body outline-none"
                        />
                        <kbd className="rounded border border-border px-1.5 text-xs font-medium text-muted-foreground">
                            Esc
                        </kbd>
                    </div>
                    <Command.List className="max-h-90 overflow-y-auto p-1.5">
                        <Command.Empty className="px-4 py-10 text-center text-muted-foreground">
                            {t('palette.empty')}
                        </Command.Empty>
                        {recent.length > 0 && (
                            <Section heading={t('palette.recent')}>
                                {recent.map((page) => (
                                    <Item
                                        key={`recent-${page.url}`}
                                        value={`recent ${page.title} ${page.url}`}
                                        icon={<History className="size-4" />}
                                        label={page.title}
                                        onSelect={() => go(page.url)}
                                    />
                                ))}
                            </Section>
                        )}
                        <Section heading={t('palette.navigation')}>
                            {items.map((item) => (
                                <Item
                                    key={`nav-${item.slug}`}
                                    value={`${item.label} ${item.group}`}
                                    icon={
                                        <Icon
                                            name={item.icon}
                                            className="size-4"
                                        />
                                    }
                                    label={item.label}
                                    hint={item.group}
                                    onSelect={() => go(item.href)}
                                />
                            ))}
                        </Section>
                        {actions.length > 0 && (
                            <Section heading={t('palette.actions')}>
                                {actions.map((item) => (
                                    <Item
                                        key={`create-${item.slug}`}
                                        value={`${t('palette.create', { label: item.label })} ${item.group}`}
                                        icon={<Plus className="size-4" />}
                                        label={t('palette.create', {
                                            label: item.label,
                                        })}
                                        hint={item.group}
                                        onSelect={() =>
                                            go(item.create_href ?? item.href)
                                        }
                                    />
                                ))}
                            </Section>
                        )}
                    </Command.List>
                    <div className="flex gap-4 border-t border-border px-3.5 py-2.5 text-xs text-muted-foreground">
                        <span>{t('palette.hint_select')}</span>
                        <span>{t('palette.hint_open')}</span>
                        <span>{t('palette.hint_close')}</span>
                    </div>
                </Command>
            </DialogContent>
        </Dialog>
    );
}

function Section({
    heading,
    children,
}: {
    heading: string;
    children: ReactNode;
}) {
    return (
        <Command.Group
            heading={heading}
            className="pb-1 [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:pt-2 [&_[cmdk-group-heading]]:pb-1 [&_[cmdk-group-heading]]:text-label [&_[cmdk-group-heading]]:text-muted-foreground [&_[cmdk-group-heading]]:uppercase"
        >
            {children}
        </Command.Group>
    );
}

function Item({
    value,
    icon,
    label,
    hint,
    onSelect,
}: {
    value: string;
    icon: ReactNode;
    label: string;
    hint?: string;
    onSelect: () => void;
}) {
    return (
        <Command.Item
            value={value}
            onSelect={onSelect}
            className="flex h-control cursor-pointer items-center gap-2.5 rounded px-2 text-body data-[selected=true]:bg-accent"
        >
            {icon}
            <span className="flex-1 truncate">{label}</span>
            {hint && (
                <span className="text-xs text-muted-foreground">{hint}</span>
            )}
        </Command.Item>
    );
}
