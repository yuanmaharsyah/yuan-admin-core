import { Button } from '../ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '../ui/dialog';
import { useT } from '../../lib/i18n';
import type { ReactNode } from 'react';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Text before the item name, e.g. "Hapus invoice". */
    title: string;
    /** The item, in bold; for many rows the caller passes "3 item". */
    itemName: string;
    description?: string;
    confirmLabel?: string;
    destructive?: boolean;
    processing?: boolean;
    onConfirm: () => void;
    children?: ReactNode;
};

/**
 * Asks before a destructive action and names the item (frontend-behavior §7).
 * Focus starts on Batal, so Enter never confirms by accident.
 */
export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    itemName,
    description,
    confirmLabel,
    destructive = true,
    processing = false,
    onConfirm,
    children,
}: Props) {
    const t = useT();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                role="alertdialog"
                hideClose
                className="gap-4 sm:max-w-110"
            >
                <DialogTitle className="border-b-2 border-rule pb-2.5 text-card-title leading-snug">
                    {title} <strong className="font-bold">{itemName}</strong>?
                </DialogTitle>
                <DialogDescription className="text-pretty">
                    {description ?? t('dialog.delete_text')}
                </DialogDescription>
                {children}
                <div className="flex flex-wrap justify-end gap-2">
                    <DialogClose asChild>
                        <Button
                            variant="outline"
                            className="bg-transparent font-semibold"
                            autoFocus
                        >
                            {t('dialog.cancel')}
                        </Button>
                    </DialogClose>
                    <Button
                        variant={destructive ? 'destructive' : 'default'}
                        className="font-semibold"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        {confirmLabel ?? t('dialog.delete')}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
