import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleAlert } from 'lucide-react';
import { AuthCard } from '../../components/admin/AuthCard';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { PasswordInput } from '../../components/admin/PasswordInput';
import { Alert, AlertDescription, AlertTitle } from '../../components/ui/alert';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { useAction } from '../../hooks/useAction';
import { useT } from '../../lib/i18n';

export default function ResetPassword(
    props: Yuan.AdminCore.Data.Auth.ResetPasswordPageData,
) {
    const t = useT();
    const sending = useAction();
    const form = useForm({
        token: props.token,
        email: props.email,
        password: '',
        password_confirmation: '',
    });
    const submit = () =>
        sending.run((done) =>
            form.post(props.submit_href, {
                onFinish: () => {
                    done();
                    form.reset('password', 'password_confirmation');
                },
            }),
        );
    const back = (
        <Link
            href={props.login_href}
            className="flex items-center justify-center gap-1.5 font-medium"
        >
            <ArrowLeft className="size-4" />
            {t('auth.back_to_login')}
        </Link>
    );

    // Fortify reports a bad token on the email field.
    if (!props.token_valid || form.errors.email) {
        return (
            <>
                <Head title={t('auth.reset_title')} />
                <AuthCard title={t('auth.reset_title')}>
                    <Alert variant="destructive">
                        <CircleAlert />
                        <div>
                            <AlertTitle>
                                {t('auth.invalid_link_title')}
                            </AlertTitle>
                            <AlertDescription>
                                {t('auth.invalid_link')}
                            </AlertDescription>
                        </div>
                    </Alert>
                    <Button
                        asChild
                        size="auth"
                        className="w-full font-semibold"
                    >
                        <Link href={props.forgot_href}>
                            {t('auth.request_new_link')}
                        </Link>
                    </Button>
                    {back}
                </AuthCard>
            </>
        );
    }

    return (
        <>
            <Head title={t('auth.reset_title')} />
            <AuthCard
                title={t('auth.reset_title')}
                description={t('auth.reset_desc')}
                onSubmit={submit}
            >
                <FormField name="email" label={t('auth.email')}>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        autoComplete="username"
                        value={props.email}
                        readOnly
                        className="bg-muted"
                    />
                </FormField>
                <FormField
                    name="password"
                    label={t('auth.new_password')}
                    required
                    help={props.password_hint}
                    error={form.errors.password}
                >
                    <PasswordInput
                        id="password"
                        name="password"
                        autoComplete="new-password"
                        autoFocus
                        required
                        disabled={form.processing}
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                        aria-invalid={form.errors.password ? true : undefined}
                        aria-describedby={fieldMessageId('password')}
                    />
                </FormField>
                <FormField
                    name="password_confirmation"
                    label={t('auth.confirm_password')}
                    required
                    error={form.errors.password_confirmation}
                >
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autoComplete="new-password"
                        required
                        disabled={form.processing}
                        value={form.data.password_confirmation}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                        aria-describedby={fieldMessageId(
                            'password_confirmation',
                        )}
                    />
                </FormField>
                <Button
                    type="submit"
                    size="auth"
                    loading={form.processing}
                    className="w-full font-semibold"
                >
                    {t(form.processing ? 'auth.saving' : 'auth.save_password')}
                </Button>
                {back}
            </AuthCard>
        </>
    );
}
