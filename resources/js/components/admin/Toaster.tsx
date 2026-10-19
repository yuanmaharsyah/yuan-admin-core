import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { Toaster as Sonner, toast } from 'sonner';
import { useAppearance } from '../../hooks/useAppearance';
import { useT } from '../../lib/i18n';

/**
 * Toasts from Inertia's native flash (frontend-behavior §2): success 4s, error
 * until closed, info 6s. Flash is never replayed from browser history, so Back
 * doesn't show a toast again. Flat card with a 1px border, bottom-right.
 */
export function Toaster() {
    const t = useT();
    const { resolvedAppearance } = useAppearance();

    useEffect(
        () =>
            router.on('flash', (event) => {
                const { success, error, info } = event.detail.flash;

                if (success) {
                    toast.success(success, { duration: 4000 });
                }

                if (error) {
                    toast.error(error, { duration: Infinity });
                }

                if (info) {
                    toast.info(info, { duration: 6000 });
                }
            }),
        [],
    );

    return (
        <Sonner
            theme={resolvedAppearance}
            position="bottom-right"
            closeButton
            containerAriaLabel={t('toast.region')}
            toastOptions={{
                unstyled: true,
                classNames: {
                    toast: 'flex w-full items-start gap-3 rounded border border-border bg-popover p-3.5 text-popover-foreground sm:w-90',
                    title: 'text-body font-semibold',
                    description: 'text-table text-muted-foreground',
                    icon: 'mt-0.5 shrink-0',
                    success: '[&_[data-icon]]:text-success',
                    error: '[&_[data-icon]]:text-destructive',
                    info: '[&_[data-icon]]:text-info',
                    closeButton:
                        'order-last ml-auto flex size-control-compact shrink-0 items-center justify-center rounded text-muted-foreground hover:bg-accent',
                },
            }}
        />
    );
}
