import { Head, useForm } from '@inertiajs/react';
import { AuthCard } from '../../components/admin/AuthCard';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { PasswordInput } from '../../components/admin/PasswordInput';
import { Button } from '../../components/ui/button';
import { useAction } from '../../hooks/useAction';
import { useT } from '../../lib/i18n';

/** Asked again before a sensitive change, e.g. turning two-step verification on or off. */
export default function ConfirmPassword(
    props: Yuan.AdminCore.Data.Auth.ConfirmPasswordPageData,
) {
    const t = useT();
    const sending = useAction();
    const form = useForm({ password: '' });
    const submit = () =>
        sending.run((done) =>
            form.post(props.submit_href, {
                onFinish: () => {
                    done();
                    form.reset('password');
                },
            }),
        );

    return (
        <>
            <Head title={t('auth.confirm_title')} />
            <AuthCard
                title={t('auth.confirm_title')}
                description={t('auth.confirm_desc')}
                onSubmit={submit}
            >
                <FormField
                    name="password"
                    label={t('auth.password')}
                    required
                    error={form.errors.password}
                >
                    <PasswordInput
                        id="password"
                        name="password"
                        autoComplete="current-password"
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
                <Button
                    type="submit"
                    size="auth"
                    loading={form.processing}
                    className="w-full font-semibold"
                >
                    {t(
                        form.processing
                            ? 'auth.processing'
                            : 'auth.confirm_submit',
                    )}
                </Button>
            </AuthCard>
        </>
    );
}
