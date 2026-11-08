import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppMark } from '../components/admin/AppMark';
import { AuthShowcase } from '../components/admin/AuthShowcase';

/**
 * The layout of the auth and error pages (ui-design §4.7). Auth pages
 * (`Core::Auth/*`): one split card, the form on the left and our showcase on
 * the right (≥ 1024px). Error pages: the same card, narrow and centered.
 */
export function AuthLayout({ children }: { children: ReactNode }) {
    const { app } = usePage().props;
    const split = usePage().component.startsWith('Core::Auth/');

    return (
        <div className="flex min-h-dvh items-center justify-center bg-background px-4 py-8 text-foreground lg:px-8">
            <main
                className={
                    split
                        ? 'grid w-full max-w-6xl overflow-hidden rounded-xl border border-border bg-card text-card-foreground lg:grid-cols-2'
                        : 'w-full max-w-lg overflow-hidden rounded-xl border border-border bg-card text-card-foreground'
                }
            >
                <div className="flex min-w-0 flex-col gap-8 p-6 sm:p-10 lg:p-14">
                    <div className="flex items-center gap-3">
                        <AppMark name={app.name} logo={app.logo} />
                        <span className="text-brand">{app.name}</span>
                    </div>
                    <div className="flex flex-col gap-6">{children}</div>
                    <p className="m-0 mt-auto text-xs text-muted-foreground">
                        © {new Date().getFullYear()} {app.name}
                    </p>
                </div>
                {split && <AuthShowcase />}
            </main>
        </div>
    );
}
