import { useState } from 'react';
import { useT } from '../../lib/i18n';
import { Input } from '../ui/input';
import { ConfirmDialog } from './ConfirmDialog';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    itemName: string;
    loading?: boolean;
    onConfirm: () => void;
};

/** The second confirm of a permanent delete: the item's name must be typed (frontend-behavior §7). */
export function ForceDeleteDialog({
    open,
    onOpenChange,
    itemName,
    loading = false,
    onConfirm,
}: Props) {
    const t = useT();
    const [typed, setTyped] = useState('');

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={(next) => {
                setTyped('');
                onOpenChange(next);
            }}
            title={t('trash.force_delete')}
            itemName={itemName}
            description={t('trash.force_text', { name: itemName })}
            confirmLabel={t('trash.force_delete')}
            confirmDisabled={typed.trim() !== itemName.trim()}
            loading={loading}
            onConfirm={onConfirm}
        >
            <Input
                aria-label={t('trash.force_input')}
                value={typed}
                onChange={(event) => setTyped(event.target.value)}
                autoComplete="off"
            />
        </ConfirmDialog>
    );
}
