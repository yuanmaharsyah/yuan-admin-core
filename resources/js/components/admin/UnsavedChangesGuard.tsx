import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useT } from '../../lib/i18n';
import { ConfirmDialog } from './ConfirmDialog';

/**
 * While `when` is true, leaving the page asks first (frontend-behavior §6):
 * Inertia visits to another page open the dialog, and closing or reloading
 * the tab gets the browser's own prompt. Submitting the form and reloading
 * the same page are never blocked.
 */
export function UnsavedChangesGuard({ when }: { when: boolean }) {
    const t = useT();
    const [pending, setPending] = useState<string | null>(null);
    const leaving = useRef(false);

    useEffect(() => {
        if (!when) {
            return;
        }

        const removeBefore = router.on('before', (event) => {
            const { method, url } = event.detail.visit;

            if (
                leaving.current ||
                method !== 'get' ||
                url.pathname === window.location.pathname
            ) {
                return;
            }

            event.preventDefault();
            setPending(url.href);
        });
        const onUnload = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', onUnload);

        return () => {
            removeBefore();
            window.removeEventListener('beforeunload', onUnload);
        };
    }, [when]);

    return (
        <ConfirmDialog
            open={pending !== null}
            onOpenChange={(open) => !open && setPending(null)}
            title={t('form.unsaved_title')}
            description={t('form.unsaved_text')}
            cancelLabel={t('form.unsaved_stay')}
            confirmLabel={t('form.unsaved_leave')}
            onConfirm={() => {
                if (pending) {
                    leaving.current = true;
                    router.visit(pending);
                }

                setPending(null);
            }}
        />
    );
}
