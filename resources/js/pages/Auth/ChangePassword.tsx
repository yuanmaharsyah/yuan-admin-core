import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Info, LogOut } from 'lucide-react';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { AuthCard } from '../../components/admin/AuthCard';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { PasswordInput } from '../../components/admin/PasswordInput';
import { Button } from '../../components/ui/button';
import { Spinner } from '../../components/ui/spinner';
import { useT } from '../../lib/i18n';
import { initials } from '../../lib/shell';

export default function ChangePassword(
    props: Yuan.AdminCore.Data.Auth.ChangePasswordPageData,
) {
    const t = useT();
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const submit = () =>
        form.put(props.submit_href, {
            onFinish: () =>
                form.reset(
                    'current_password',
                    'password',
                    'password_confirmation',
                ),
        });
    const field = (
        name: 'current_password' | 'password' | 'password_confirmation',
        autoComplete: string,
        autoFocus = false,
    ) => (
        <PasswordInput
            id={name}
            name={name}
            autoComplete={autoComplete}
            autoFocus={autoFocus}
            required
            disabled={form.processing}
            value={form.data[name]}
            onChange={(event) => form.setData(name, event.target.value)}
            aria-invalid={form.errors[name] ? true : undefined}
            aria-describedby={fieldMessageId(name)}
        />
    );

    return (
        <>
            <Head title={t('auth.change_title')} />
            <AuthCard title={t('auth.change_title')} onSubmit={submit}>
                {props.required && (
                    <Alert variant="info">
                        <Info />
                        <AlertDescription>
                            {t('auth.change_required')}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="flex items-center gap-2.5 rounded bg-muted px-3 py-2.5 text-table">
                    <span
                        aria-hidden="true"
                        className="flex size-7 shrink-0 items-center justify-center rounded-full bg-card text-label"
                    >
                        {initials(props.user_name)}
                    </span>
                    <span className="flex flex-col">
                        <span className="font-semibold">{props.user_name}</span>
                        {props.level_label && (
                            <span className="text-muted-foreground">
                                {props.level_label}
                            </span>
                        )}
                    </span>
                </div>
                <FormField
                    name="current_password"
                    label={t('auth.current_password')}
                    required
                    error={form.errors.current_password}
                >
                    {field('current_password', 'current-password', true)}
                </FormField>
                <FormField
                    name="password"
                    label={t('auth.new_password')}
                    required
                    help={props.password_hint}
                    error={form.errors.password}
                >
                    {field('password', 'new-password')}
                </FormField>
                <FormField
                    name="password_confirmation"
                    label={t('auth.confirm_new_password')}
                    required
                    error={form.errors.password_confirmation}
                >
                    {field('password_confirmation', 'new-password')}
                </FormField>
                <Button
                    type="submit"
                    size="auth"
                    disabled={form.processing}
                    aria-busy={form.processing}
                    className="w-full font-semibold"
                >
                    {form.processing && <Spinner />}
                    {t(form.processing ? 'auth.saving' : 'auth.save_password')}
                </Button>
                {props.required ? (
                    <Link
                        href={props.logout_href}
                        method="post"
                        as="button"
                        className="flex items-center justify-center gap-1.5 font-medium"
                    >
                        <LogOut className="size-4" />
                        {t('shell.logout')}
                    </Link>
                ) : (
                    <Link
                        href={props.back_href}
                        className="flex items-center justify-center gap-1.5 font-medium"
                    >
                        <ArrowLeft className="size-4" />
                        {t('common.back')}
                    </Link>
                )}
            </AuthCard>
        </>
    );
}
