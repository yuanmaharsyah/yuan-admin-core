import {
    fieldMessageId,
    FormField,
} from '@admin-core/components/admin/FormField';
import { PasswordInput } from '@admin-core/components/admin/PasswordInput';
import { Button } from '@admin-core/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@admin-core/components/ui/dialog';
import { useAction } from '@admin-core/hooks/useAction';
import { useT } from '@admin-core/lib/i18n';
import { cn } from '@admin-core/lib/utils';
import { useForm } from '@inertiajs/react';
import { resetPassword } from '@/routes/users';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user: Yuan.AdminCore.Modules.Users.Data.UserData;
    passwordHint: string;
};

/**
 * Reset another user's password (derived design: the confirm dialog with a
 * choice). A generated password comes back once as `flash.secret`.
 */
export function ResetPasswordDialog({
    open,
    onOpenChange,
    user,
    passwordHint,
}: Props) {
    const t = useT();
    const sending = useAction();
    const form = useForm<{
        mode: 'generate' | 'set';
        password: string;
        password_confirmation: string;
    }>({
        mode: 'generate',
        password: '',
        password_confirmation: '',
    });
    const close = (next: boolean) => {
        if (!next) {
            form.reset();
            form.clearErrors();
        }

        onOpenChange(next);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (sending.pending ? undefined : close(next))}
        >
            <DialogContent
                role="alertdialog"
                hideClose
                className="gap-4 sm:max-w-110"
            >
                <DialogTitle className="border-b-2 border-rule pb-2.5 text-card-title leading-snug">
                    {t('users.reset_title')}{' '}
                    <strong className="font-bold">{user.name}</strong>?
                </DialogTitle>
                <DialogDescription className="text-pretty">
                    {t('users.reset_text')}
                </DialogDescription>
                <form
                    noValidate
                    className="flex flex-col gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        sending.run((done) =>
                            form.submit(resetPassword(user.id), {
                                preserveScroll: true,
                                onSuccess: () => close(false),
                                onFinish: done,
                            }),
                        );
                    }}
                >
                    <div
                        role="radiogroup"
                        aria-label={t('users.reset_title')}
                        className="grid grid-cols-2 gap-2"
                    >
                        {(['generate', 'set'] as const).map((mode) => (
                            <button
                                key={mode}
                                type="button"
                                role="radio"
                                aria-checked={form.data.mode === mode}
                                onClick={() => form.setData('mode', mode)}
                                className={cn(
                                    'h-control rounded border px-3 text-table font-medium',
                                    form.data.mode === mode
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border hover:bg-accent',
                                )}
                            >
                                {t(
                                    mode === 'generate'
                                        ? 'users.reset_generate'
                                        : 'users.reset_set',
                                )}
                            </button>
                        ))}
                    </div>
                    {form.data.mode === 'set' && (
                        <>
                            <FormField
                                name="reset_password"
                                label={t('users.password')}
                                required
                                help={passwordHint}
                                error={form.errors.password}
                            >
                                <PasswordInput
                                    id="reset_password"
                                    autoComplete="new-password"
                                    value={form.data.password}
                                    onChange={(event) =>
                                        form.setData(
                                            'password',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={
                                        form.errors.password ? true : undefined
                                    }
                                    aria-describedby={fieldMessageId(
                                        'reset_password',
                                    )}
                                />
                            </FormField>
                            <FormField
                                name="reset_password_confirmation"
                                label={t('users.password_confirmation')}
                                required
                            >
                                <PasswordInput
                                    id="reset_password_confirmation"
                                    autoComplete="new-password"
                                    value={form.data.password_confirmation}
                                    onChange={(event) =>
                                        form.setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    aria-describedby={fieldMessageId(
                                        'reset_password_confirmation',
                                    )}
                                />
                            </FormField>
                        </>
                    )}
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="bg-transparent font-semibold"
                            autoFocus
                            onClick={() => close(false)}
                        >
                            {t('dialog.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            className="font-semibold"
                            loading={sending.pending}
                        >
                            {t('users.reset_submit')}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
