import { usePage } from '@inertiajs/react';
import { Menu, Search, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { AppRail } from '../components/admin/AppRail';
import { Breadcrumbs } from '../components/admin/Breadcrumbs';
import { CommandPalette } from '../components/admin/CommandPalette';
import { ItemPanel } from '../components/admin/ItemPanel';
import { MaintenanceBanner } from '../components/admin/MaintenanceBanner';
import { ThemeToggle } from '../components/admin/ThemeToggle';
import { UserMenu } from '../components/admin/UserMenu';
import { Button } from '../components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '../components/ui/sheet';
import { useRecordRecentPage } from '../hooks/useRecentPages';
import { useT } from '../lib/i18n';
import type { ShellLinks } from '../lib/shell';
import { activeMenu } from '../lib/shell';

/** Something opened on one page; it closes by itself on the next navigation. */
type OpenOn = { url: string; key?: string } | null;

function writePanelCookie(open: boolean): void {
    document.cookie = `admin_panel=${open};path=/;max-age=31536000;SameSite=Lax`;
}

/**
 * The admin shell (ui-design §2, frontend-behavior §3): group rail + item panel
 * (≥ 1024px; a sheet below), topbar, maintenance banner, content and the
 * command palette.
 */
export function AdminLayout({
    children,
    links,
}: {
    children: ReactNode;
    links: ShellLinks;
}) {
    const t = useT();
    const { props, url } = usePage();
    const { menu, app, auth, breadcrumbs } = props;
    const active = activeMenu(menu, app.route);
    const [panelOpen, setPanelOpen] = useState(app.panel_open);
    // Viewed group (may differ from the active one until the next navigation).
    const [viewed, setViewed] = useState<OpenOn>(null);
    const [overlay, setOverlay] = useState<OpenOn>(null);
    const [sheet, setSheet] = useState<OpenOn>(null);
    const [palette, setPalette] = useState<OpenOn>(null);
    const viewedKey =
        (viewed?.url === url ? viewed.key : undefined) ??
        active.group?.key ??
        menu[0]?.key;
    const viewedGroup = menu.find((group) => group.key === viewedKey);
    const overlayOpen = overlay?.url === url;
    const sheetOpen = sheet?.url === url;
    const paletteOpen = palette?.url === url;

    useRecordRecentPage(active.item ? { title: active.item.label, url } : null);

    const togglePanel = () => {
        setPanelOpen((open) => {
            writePanelCookie(!open);

            return !open;
        });
        setOverlay(null);
    };

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (!(event.ctrlKey || event.metaKey)) {
                return;
            }

            if (event.key.toLowerCase() === 'k') {
                event.preventDefault();
                setPalette(paletteOpen ? null : { url });
            } else if (event.key.toLowerCase() === 'b') {
                event.preventDefault();
                togglePanel();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    });

    useEffect(() => {
        if (!overlayOpen) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOverlay(null);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [overlayOpen]);

    const selectGroup = (key: string) => {
        setViewed({ url, key });

        if (!panelOpen) {
            setOverlay({ url });
        }
    };

    const panel = (touch = false, action?: ReactNode) => (
        <ItemPanel
            appName={app.name}
            group={viewedGroup}
            activeSlug={active.item?.slug}
            auth={auth}
            profile={links.profile}
            action={action}
            touch={touch}
        />
    );

    return (
        <div className="flex h-dvh overflow-hidden bg-background text-foreground">
            <div className="relative z-30 hidden h-full shrink-0 lg:flex">
                <AppRail
                    menu={menu}
                    appName={app.name}
                    activeKey={active.group?.key}
                    onSelectGroup={selectGroup}
                    logout={links.logout}
                    panel={{ open: panelOpen, onToggle: togglePanel }}
                />
                {panelOpen && panel()}
                {!panelOpen && overlayOpen && (
                    <>
                        <button
                            type="button"
                            aria-label={t('common.close')}
                            tabIndex={-1}
                            className="fixed inset-0 z-0 cursor-default"
                            onClick={() => setOverlay(null)}
                        />
                        <div className="absolute inset-y-0 left-full z-10 shadow-sheet">
                            {panel()}
                        </div>
                    </>
                )}
            </div>

            <Sheet
                open={sheetOpen}
                onOpenChange={(open) => setSheet(open ? { url } : null)}
            >
                <SheetContent
                    side="left"
                    className="flex w-75 max-w-[85%] flex-row gap-0 border-0 p-0 [&>button:last-child]:hidden"
                    aria-describedby={undefined}
                >
                    <SheetTitle className="sr-only">
                        {t('shell.navigation')}
                    </SheetTitle>
                    <AppRail
                        menu={menu}
                        appName={app.name}
                        activeKey={active.group?.key}
                        onSelectGroup={(key) => setViewed({ url, key })}
                        logout={links.logout}
                        touch
                    />
                    <div className="min-w-0 flex-1 [&>nav]:w-full">
                        {panel(
                            true,
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={t('shell.close_menu')}
                                className="-mt-2.5 -mr-2.5 size-row"
                                onClick={() => setSheet(null)}
                            >
                                <X className="size-4.5" />
                            </Button>,
                        )}
                    </div>
                </SheetContent>
            </Sheet>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex h-topbar shrink-0 items-center gap-3 px-4 lg:px-8">
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={t('shell.open_menu')}
                        aria-expanded={sheetOpen}
                        className="-ml-2 lg:hidden"
                        onClick={() => setSheet({ url })}
                    >
                        <Menu className="size-5" />
                    </Button>
                    <span className="truncate font-bold lg:hidden">
                        {app.name}
                    </span>
                    <div className="hidden min-w-0 lg:block">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                    <div className="flex-1" />
                    <button
                        type="button"
                        onClick={() => setPalette({ url })}
                        className="hidden h-control-sm w-60 items-center gap-2 rounded border border-border bg-card pr-1.5 pl-2.5 text-table text-muted-foreground lg:flex"
                    >
                        <Search aria-hidden="true" className="size-4" />
                        <span className="flex-1 text-left">
                            {t('shell.search')}
                        </span>
                        <kbd className="rounded border border-border bg-muted px-1.5 text-xs font-medium text-foreground">
                            Ctrl K
                        </kbd>
                    </button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={t('shell.search_shortcut')}
                        className="lg:hidden"
                        onClick={() => setPalette({ url })}
                    >
                        <Search className="size-4.5" />
                    </Button>
                    <div className="hidden lg:block">
                        <ThemeToggle />
                    </div>
                    {auth.user && <UserMenu user={auth.user} links={links} />}
                </header>
                <MaintenanceBanner message={app.maintenance_banner} />
                <main className="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-4 pt-2 pb-4 lg:px-8 lg:pt-4 lg:pb-8">
                    {children}
                </main>
            </div>

            <CommandPalette
                open={paletteOpen}
                onOpenChange={(open) => setPalette(open ? { url } : null)}
                menu={menu}
            />
        </div>
    );
}
