import { Button } from '@admin-core/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@admin-core/components/ui/dialog';
import { useT } from '@admin-core/lib/i18n';
import { Check, Copy } from 'lucide-react';
import { useState } from 'react';

/** Shows a generated password once, with a copy button. */
export function TemporaryPasswordDialog({
    password,
    onClose,
}: {
    password: string | null;
    onClose: () => void;
}) {
    const t = useT();
    const [copied, setCopied] = useState(false);

    return (
        <Dialog
            open={password !== null}
            onOpenChange={(open) => {
                if (!open) {
                    setCopied(false);
                    onClose();
                }
            }}
        >
            <DialogContent hideClose className="gap-4 sm:max-w-110">
                <DialogTitle className="border-b-2 border-rule pb-2.5 text-card-title">
                    {t('users.temporary_title')}
                </DialogTitle>
                <DialogDescription className="text-pretty">
                    {t('users.temporary_text')}
                </DialogDescription>
                <div className="flex items-center gap-2 rounded border border-border bg-muted px-3 py-2">
                    <code className="flex-1 font-mono text-body tracking-wide break-all select-all">
                        {password}
                    </code>
                    <Button
                        variant="outline"
                        size="compact"
                        className="bg-card"
                        onClick={() =>
                            void navigator.clipboard
                                .writeText(password ?? '')
                                .then(() => setCopied(true))
                        }
                    >
                        {copied ? <Check /> : <Copy />}
                        {t(copied ? 'users.copied' : 'users.copy')}
                    </Button>
                </div>
                <div className="flex justify-end">
                    <Button
                        className="font-semibold"
                        autoFocus
                        onClick={() => {
                            setCopied(false);
                            onClose();
                        }}
                    >
                        {t('users.done')}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
