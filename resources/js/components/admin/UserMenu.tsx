import { Link, router } from '@inertiajs/react';
import { KeyRound, LogOut, Moon, Sun, UserRound } from 'lucide-react';
import { useAppearance } from '../../hooks/useAppearance';
import { useT } from '../../lib/i18n';
import type { ShellLinks } from '../../lib/shell';
import { initials } from '../../lib/shell';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';

export function UserMenu({
    user,
    links,
}: {
    user: Yuan.AdminCore.Data.UserData;
    links: ShellLinks;
}) {
    const t = useT();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const dark = resolvedAppearance === 'dark';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                aria-label={t('shell.user_menu', { name: user.name })}
                className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-label text-foreground"
            >
                {initials(user.name)}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="flex flex-col font-normal">
                    <span className="truncate text-body font-semibold">
                        {user.name}
                    </span>
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={links.profile} className="w-full">
                        <UserRound />
                        {t('shell.profile')}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href={links.changePassword} className="w-full">
                        <KeyRound />
                        {t('shell.change_password')}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    onSelect={() => updateAppearance(dark ? 'light' : 'dark')}
                >
                    {dark ? <Sun /> : <Moon />}
                    {t(dark ? 'shell.theme_light' : 'shell.theme_dark')}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link
                        href={links.logout}
                        as="button"
                        className="w-full"
                        onClick={() => router.flushAll()}
                    >
                        <LogOut />
                        {t('shell.logout')}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
