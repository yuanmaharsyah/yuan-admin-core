import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AuthCard } from '../../components/admin/AuthCard';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { Alert, AlertDescription, AlertTitle } from '../../components/ui/alert';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Spinner } from '../../components/ui/spinner';
import { useT } from '../../lib/i18n';

export default function ForgotPassword(
    props: Yuan.AdminCore.Data.Auth.ForgotPasswordPageData,
) {
    const t = useT();
    const form = useForm({ email: '' });
    // Bumped on each successful request, so the resend countdown restarts.
    const [sends, setSends] = useState(0);
    const submit = () =>
        form.post(props.submit_href, {
            preserveState: true,
            onSuccess: () => setSends((count) => count + 1),
        });
    const back = (
        <Link
            href={props.login_href}
            className="flex items-center justify-center gap-1.5 font-medium"
        >
            <ArrowLeft className="size-4" />
            {t('auth.back_to_login')}
        </Link>
    );

    if (props.status) {
        return (
            <>
                <Head title={t('auth.sent_title')} />
                <AuthCard title={t('auth.sent_title')} onSubmit={submit}>
                    <Alert variant="success">
                        <CircleCheck />
                        <div>
                            <AlertTitle>
                                {t('auth.sent_alert_title')}
                            </AlertTitle>
                            <AlertDescription>{props.status}</AlertDescription>
                        </div>
                    </Alert>
                    {form.errors.email && (
                        <span
                            role="alert"
                            className="text-table text-destructive"
                        >
                            {form.errors.email}
                        </span>
                    )}
                    <ResendButton
                        key={sends}
                        seconds={props.resend_after}
                        busy={form.processing}
                    />
                    {back}
                </AuthCard>
            </>
        );
    }

    return (
        <>
            <Head title={t('auth.forgot_title')} />
            <AuthCard
                title={t('auth.forgot_title')}
                description={t('auth.forgot_desc')}
                onSubmit={submit}
            >
                <FormField
                    name="email"
                    label={t('auth.email')}
                    required
                    error={form.errors.email}
                >
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        autoComplete="email"
                        autoFocus
                        required
                        disabled={form.processing}
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                        aria-invalid={form.errors.email ? true : undefined}
                        aria-describedby={fieldMessageId('email')}
                    />
                </FormField>
                <Button
                    type="submit"
                    size="auth"
                    disabled={form.processing}
                    aria-busy={form.processing}
                    className="w-full font-semibold"
                >
                    {form.processing && <Spinner />}
                    {t(form.processing ? 'auth.sending' : 'auth.forgot_submit')}
                </Button>
                {back}
            </AuthCard>
        </>
    );
}

/** "Kirim ulang", disabled while the reset-link throttle runs. */
function ResendButton({ seconds, busy }: { seconds: number; busy: boolean }) {
    const t = useT();
    const [left, setLeft] = useState(seconds);

    useEffect(() => {
        const timer = window.setInterval(
            () => setLeft((value) => Math.max(0, value - 1)),
            1000,
        );

        return () => window.clearInterval(timer);
    }, []);

    const time = `${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}`;

    return (
        <Button
            type="submit"
            size="auth"
            variant="outline"
            disabled={left > 0 || busy}
            aria-busy={busy}
            className="w-full font-semibold tabular-nums"
        >
            {busy && <Spinner />}
            {left > 0 ? t('auth.resend_in', { time }) : t('auth.resend')}
        </Button>
    );
}
