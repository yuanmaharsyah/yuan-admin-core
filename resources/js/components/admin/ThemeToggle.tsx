import { Moon, Sun } from 'lucide-react';
import { useAppearance } from '../../hooks/useAppearance';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';

/** Switches between light and dark; the choice is kept in the `appearance` cookie. */
export function ThemeToggle() {
    const t = useT();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const dark = resolvedAppearance === 'dark';

    return (
        <Button
            variant="ghost"
            size="icon"
            aria-label={t('shell.theme')}
            aria-pressed={dark}
            onClick={() => updateAppearance(dark ? 'light' : 'dark')}
        >
            {dark ? (
                <Sun className="size-4.5" />
            ) : (
                <Moon className="size-4.5" />
            )}
        </Button>
    );
}
