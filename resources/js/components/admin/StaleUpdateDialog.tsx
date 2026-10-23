import { useAction } from '../../hooks/useAction';
import { useT } from '../../lib/i18n';
import { ConfirmDialog } from './ConfirmDialog';

type Props = {
    open: boolean;
    /** The fields the user changed, copied as JSON by "Salin perubahan saya". */
    dirtyData: Record<string, unknown>;
    onReload: (onFinish: () => void) => void;
};

/**
 * Shown on a 409 (frontend-behavior §6, derived design): someone else saved
 * this record since the form loaded. Both choices reload the page; one copies
 * the user's changes to the clipboard first.
 */
export function StaleUpdateDialog({ open, dirtyData, onReload }: Props) {
    const t = useT();
    const reloading = useAction();

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={() => undefined}
            title={t('form.stale_title')}
            description={t('form.stale_text')}
            destructive={false}
            cancelLabel={false}
            secondaryLabel={t('form.stale_copy')}
            loading={reloading.pending}
            onSecondary={() =>
                reloading.run((done) => {
                    void navigator.clipboard
                        .writeText(JSON.stringify(dirtyData, null, 2))
                        .finally(() => onReload(done));
                })
            }
            confirmLabel={t('form.stale_reload')}
            onConfirm={() => reloading.run(onReload)}
        />
    );
}
