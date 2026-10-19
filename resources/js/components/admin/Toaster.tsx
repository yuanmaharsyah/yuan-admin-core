import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Toaster as Sonner, toast } from 'sonner';
import { useAppearance } from '../../hooks/useAppearance';
import { useT } from '../../lib/i18n';

const UNDO_SECONDS = 8;

/**
 * Toasts from Inertia's native flash (frontend-behavior §2): success 4s, error
 * until closed, info 6s. Flash is never replayed from browser history, so Back
 * doesn't show a toast again. Flat card with a 1px border, bottom-right. A
 * flash with `undo` (Super Admin, after a soft delete) shows "Urungkan · 8"
 * for 8 seconds, then restores through the posted route (frontend-behavior §7).
 */
export function Toaster() {
    const t = useT();
    const { resolvedAppearance } = useAppearance();

    useEffect(
        () =>
            router.on('flash', (event) => {
                const { success, error, info, description, undo } =
                    event.detail.flash;

                if (success) {
                    toast.success(success, {
                        description,
                        duration: undo ? UNDO_SECONDS * 1000 : 4000,
                        action: undo && {
                            label: <UndoLabel />,
                            onClick: () =>
                                router.post(
                                    undo.href,
                                    { ids: undo.ids },
                                    { preserveScroll: true },
                                ),
                        },
                    });
                }

                if (error) {
                    toast.error(error, { description, duration: Infinity });
                }

                if (info) {
                    toast.info(info, { description, duration: 6000 });
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
                    content: 'min-w-0 flex-1',
                    title: 'text-body font-semibold',
                    description: 'text-table text-muted-foreground',
                    icon: 'mt-0.5 shrink-0',
                    success: '[&_[data-icon]]:text-success',
                    error: '[&_[data-icon]]:text-destructive',
                    info: '[&_[data-icon]]:text-info',
                    actionButton:
                        'order-1 flex h-control-compact shrink-0 items-center rounded border border-border px-2.5 text-table font-semibold tabular-nums hover:bg-accent',
                    closeButton:
                        'order-2 flex size-control-compact shrink-0 items-center justify-center rounded text-muted-foreground hover:bg-accent',
                },
            }}
        />
    );
}

/** "Urungkan · 8", counting down while the toast is shown. */
function UndoLabel() {
    const t = useT();
    const [seconds, setSeconds] = useState(UNDO_SECONDS);

    useEffect(() => {
        const timer = window.setInterval(
            () => setSeconds((left) => Math.max(left - 1, 0)),
            1000,
        );

        return () => window.clearInterval(timer);
    }, []);

    return t('toast.undo', { seconds });
}
