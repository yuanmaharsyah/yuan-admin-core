import type { ReactNode } from 'react';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '../ui/dialog';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Text before the item name, e.g. "Hapus invoice"; the whole title when there is no item. */
    title: string;
    /** The item, in bold, followed by "?"; for many rows the caller passes "3 item". */
    itemName?: string;
    description?: string;
    confirmLabel?: string;
    destructive?: boolean;
    processing?: boolean;
    onConfirm: () => void;
    /** Label of the first, focused button (Batal); false leaves it out. */
    cancelLabel?: string | false;
    /** An extra action between cancel and confirm. */
    secondaryLabel?: string;
    onSecondary?: () => void;
    children?: ReactNode;
};

/**
 * Asks before a destructive or lossy action (frontend-behavior §7, ui-design
 * §3.3): names the item, and focus starts on the first button (Batal), so
 * Enter never confirms by accident.
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
    cancelLabel,
    secondaryLabel,
    onSecondary,
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
                    {itemName ? (
                        <>
                            {title}{' '}
                            <strong className="font-bold">{itemName}</strong>?
                        </>
                    ) : (
                        title
                    )}
                </DialogTitle>
                <DialogDescription className="text-pretty">
                    {description ?? t('dialog.delete_text')}
                </DialogDescription>
                {children}
                <div className="flex flex-wrap justify-end gap-2">
                    {cancelLabel !== false && (
                        <DialogClose asChild>
                            <Button
                                variant="outline"
                                className="bg-transparent font-semibold"
                                autoFocus
                            >
                                {cancelLabel ?? t('dialog.cancel')}
                            </Button>
                        </DialogClose>
                    )}
                    {secondaryLabel && (
                        <Button
                            variant="outline"
                            className="bg-transparent font-semibold"
                            autoFocus={cancelLabel === false}
                            onClick={onSecondary}
                        >
                            {secondaryLabel}
                        </Button>
                    )}
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
