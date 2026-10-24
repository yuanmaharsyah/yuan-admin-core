import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppMark } from '../components/admin/AppMark';

/**
 * Centered auth layout (ui-design §4.7, reference auth): app mark and name,
 * the card, and a footer. Also used by the error pages.
 */
export function AuthLayout({ children }: { children: ReactNode }) {
    const { app } = usePage().props;

    return (
        <div className="flex min-h-dvh items-center justify-center bg-background px-4 py-8 text-foreground">
            <main className="flex w-full max-w-100 flex-col gap-5">
                <div className="flex items-center gap-3">
                    <AppMark name={app.name} logo={app.logo} />
                    <div className="text-brand">{app.name}</div>
                </div>
                {children}
                <div className="text-xs text-muted-foreground">
                    © {new Date().getFullYear()} {app.name}
                </div>
            </main>
        </div>
    );
}
