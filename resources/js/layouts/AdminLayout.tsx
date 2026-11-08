import { usePage } from '@inertiajs/react';
import { Menu, PanelLeft, Search, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { AppSidebar } from '../components/admin/AppSidebar';
import { CommandPalette } from '../components/admin/CommandPalette';
import { DownloadsLink } from '../components/admin/DownloadsLink';
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
type OpenOn = { url: string } | null;

function writePanelCookie(open: boolean): void {
    document.cookie = `admin_panel=${open};path=/;max-age=31536000;SameSite=Lax`;
}

/**
 * The admin shell (ui-design §2, frontend-behavior §3): the sidebar (≥ 1024px,
 * collapses to icons; a sheet below), the inset main panel with the topbar,
 * the site access banner, the content and the command palette.
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
    const { menu, app, auth } = props;
    const active = activeMenu(menu, app.route);
    const [expanded, setExpanded] = useState(app.panel_open);
    const [sheet, setSheet] = useState<OpenOn>(null);
    const [palette, setPalette] = useState<OpenOn>(null);
    const sheetOpen = sheet?.url === url;
    const paletteOpen = palette?.url === url;

    useRecordRecentPage(active.item ? { title: active.item.label, url } : null);

    const toggleSidebar = () => {
        setExpanded((open) => {
            writePanelCookie(!open);

            return !open;
        });
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
                toggleSidebar();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    });

    const sidebar = {
        menu,
        appName: app.name,
        logo: app.logo,
        activeSlug: active.item?.slug,
        activeRoute: app.route,
        auth,
        profile: links.profile,
    };

    return (
        <div className="flex h-dvh overflow-hidden bg-sidebar text-foreground">
            <div className="hidden h-full shrink-0 lg:flex">
                <AppSidebar {...sidebar} collapsed={!expanded} />
            </div>

            <Sheet
                open={sheetOpen}
                onOpenChange={(open) => setSheet(open ? { url } : null)}
            >
                <SheetContent
                    side="left"
                    className="w-sidebar max-w-[85%] gap-0 border-0 p-0 [&>button:last-child]:hidden [&>nav]:w-full"
                    aria-describedby={undefined}
                >
                    <SheetTitle className="sr-only">
                        {t('shell.navigation')}
                    </SheetTitle>
                    <AppSidebar
                        {...sidebar}
                        touch
                        action={
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={t('shell.close_menu')}
                                className="-mr-2"
                                onClick={() => setSheet(null)}
                            >
                                <X className="size-4.5" />
                            </Button>
                        }
                    />
                </SheetContent>
            </Sheet>

            <div className="flex min-w-0 flex-1 flex-col lg:py-2 lg:pr-2">
                <div className="flex min-h-0 flex-1 flex-col overflow-hidden bg-background lg:rounded-xl lg:border lg:border-border">
                    <header className="flex h-topbar shrink-0 items-center gap-2 border-b border-border px-4 lg:px-6">
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
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t(
                                expanded
                                    ? 'shell.sidebar_collapse'
                                    : 'shell.sidebar_expand',
                            )}
                            aria-expanded={expanded}
                            className="-ml-2 hidden lg:inline-flex"
                            onClick={toggleSidebar}
                        >
                            <PanelLeft className="size-4.5" />
                        </Button>
                        <div
                            aria-hidden="true"
                            className="mx-1 hidden h-4 w-px bg-border lg:block"
                        />
                        <span className="truncate font-semibold lg:hidden">
                            {app.name}
                        </span>
                        <button
                            type="button"
                            onClick={() => setPalette({ url })}
                            className="hidden h-control w-80 items-center gap-2.5 rounded border border-input bg-transparent px-3 text-body text-muted-foreground transition-colors duration-150 hover:bg-accent lg:flex dark:bg-input/30"
                        >
                            <Search aria-hidden="true" className="size-4" />
                            <span className="flex-1 text-left">
                                {t('shell.search')}
                            </span>
                            <kbd className="rounded-sm border border-border bg-muted px-1.5 text-xs font-medium text-muted-foreground">
                                Ctrl K
                            </kbd>
                        </button>
                        <div className="flex-1" />
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('shell.search_shortcut')}
                            className="rounded-full lg:hidden"
                            onClick={() => setPalette({ url })}
                        >
                            <Search className="size-4.5" />
                        </Button>
                        <DownloadsLink href={links.downloads} />
                        <div className="hidden lg:block">
                            <ThemeToggle />
                        </div>
                        {auth.user && (
                            <UserMenu user={auth.user} links={links} />
                        )}
                    </header>
                    <MaintenanceBanner access={app.site_access} />
                    {/* `relative`: absolutely positioned content (screen-reader tables) scrolls with the page instead of stretching the window. */}
                    <main className="relative flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-6">
                        {children}
                    </main>
                </div>
            </div>

            <CommandPalette
                open={paletteOpen}
                onOpenChange={(open) => setPalette(open ? { url } : null)}
                menu={menu}
            />
        </div>
    );
}
