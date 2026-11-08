import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AuthCard } from '../../components/admin/AuthCard';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { useAction } from '../../hooks/useAction';
import { useT } from '../../lib/i18n';

/** The second sign-in step: the 6-digit code from the authenticator app, or a recovery code. */
export default function TwoFactorChallenge(
    props: Yuan.AdminCore.Data.Auth.TwoFactorChallengePageData,
) {
    const t = useT();
    const sending = useAction();
    const form = useForm({ code: '', recovery_code: '' });
    const [recovery, setRecovery] = useState(false);
    const field = recovery ? 'recovery_code' : 'code';
    const submit = () =>
        sending.run((done) =>
            form.post(props.submit_href, { onFinish: () => done() }),
        );
    const toggle = () => {
        form.reset();
        form.clearErrors();
        setRecovery(!recovery);
    };

    return (
        <>
            <Head title={t('auth.two_factor_title')} />
            <AuthCard
                title={t('auth.two_factor_title')}
                description={t(
                    recovery
                        ? 'auth.two_factor_recovery_desc'
                        : 'auth.two_factor_desc',
                )}
                onSubmit={submit}
            >
                <FormField
                    name={field}
                    label={t(
                        recovery
                            ? 'auth.recovery_code'
                            : 'auth.two_factor_code',
                    )}
                    required
                    error={form.errors[field]}
                >
                    <Input
                        id={field}
                        name={field}
                        autoComplete="one-time-code"
                        inputMode={recovery ? 'text' : 'numeric'}
                        autoFocus
                        required
                        disabled={form.processing}
                        value={form.data[field]}
                        onChange={(event) =>
                            form.setData(field, event.target.value)
                        }
                        aria-invalid={form.errors[field] ? true : undefined}
                        aria-describedby={fieldMessageId(field)}
                        className={
                            recovery
                                ? undefined
                                : 'text-center text-lg tracking-[0.5em] tabular-nums'
                        }
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
                            : 'auth.two_factor_submit',
                    )}
                </Button>
                <button
                    type="button"
                    onClick={toggle}
                    className="self-start font-medium text-foreground underline-offset-4 hover:underline"
                >
                    {t(recovery ? 'auth.use_code' : 'auth.use_recovery')}
                </button>
            </AuthCard>
            <Link href={props.login_href} className="text-muted-foreground">
                {t('auth.back_to_login')}
            </Link>
        </>
    );
}
