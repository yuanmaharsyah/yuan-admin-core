import { useT } from '../../lib/i18n';
import { ConfirmDialog } from './ConfirmDialog';

type Props = {
    open: boolean;
    /** The fields the user changed, copied as JSON by "Salin perubahan saya". */
    dirtyData: Record<string, unknown>;
    onReload: () => void;
};

/**
 * Shown on a 409 (frontend-behavior §6, derived design): someone else saved
 * this record since the form loaded. Both choices reload the page; one copies
 * the user's changes to the clipboard first.
 */
export function StaleUpdateDialog({ open, dirtyData, onReload }: Props) {
    const t = useT();

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={() => undefined}
            title={t('form.stale_title')}
            description={t('form.stale_text')}
            destructive={false}
            cancelLabel={false}
            secondaryLabel={t('form.stale_copy')}
            onSecondary={() => {
                void navigator.clipboard
                    .writeText(JSON.stringify(dirtyData, null, 2))
                    .finally(onReload);
            }}
            confirmLabel={t('form.stale_reload')}
            onConfirm={onReload}
        />
    );
}
