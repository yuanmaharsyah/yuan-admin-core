import { router, usePage } from '@inertiajs/react';
import { Laptop, LogOut, ShieldCheck, Smartphone } from 'lucide-react';
import { useState } from 'react';
import { useAction } from '../../hooks/useAction';
import { formatDateTime } from '../../lib/format';
import { sendJson } from '../../lib/http';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { CardHeading } from './CardHeading';
import { fieldMessageId, FormField } from './FormField';
import { PasswordInput } from './PasswordInput';
import { StatusBadge } from './StatusBadge';

type TwoFactor = Yuan.AdminCore.Data.Account.TwoFactorData;

const reload = () => router.reload({ only: ['account'] });

/**
 * Two-step verification of the signed-in user on Fortify's JSON endpoints:
 * the password again when Fortify asks for it, then set up (QR code + secret),
 * confirm with the first code, show the recovery codes, or turn it off.
 */
export function TwoFactorCard({ twoFactor }: { twoFactor: TwoFactor }) {
    const t = useT();
    const busy = useAction();
    const [askPassword, setAskPassword] = useState<null | (() => void)>(null);
    const [password, setPassword] = useState('');
    const [code, setCode] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [qr, setQr] = useState<{ svg: string; secret: string } | null>(null);
    const [codes, setCodes] = useState<string[] | null>(null);

    /** Runs `action` once Fortify's password confirmation is fresh. */
    const confirmed = (action: () => void) =>
        busy.run((done) => {
            sendJson<{ confirmed: boolean }>(twoFactor.confirm_status_href)
                .then((status) => {
                    if (status.data?.confirmed) {
                        action();
                    } else {
                        setAskPassword(() => action);
                    }
                })
                .finally(done);
        });

    const sendPassword = () =>
        busy.run((done) => {
            sendJson(twoFactor.confirm_password_href, 'POST', { password })
                .then((result) => {
                    setErrors(result.errors);

                    if (result.ok) {
                        const next = askPassword;
                        setAskPassword(null);
                        setPassword('');
                        next?.();
                    }
                })
                .finally(done);
        });

    const loadSetup = () =>
        Promise.all([
            sendJson<{ svg: string }>(twoFactor.qr_href),
            sendJson<{ secretKey: string }>(twoFactor.secret_href),
        ]).then(([image, secret]) =>
            setQr({
                svg: image.data?.svg ?? '',
                secret: secret.data?.secretKey ?? '',
            }),
        );

    const loadCodes = () =>
        sendJson<string[]>(twoFactor.recovery_href).then((result) =>
            setCodes(result.data ?? []),
        );

    const enable = () =>
        sendJson(twoFactor.enable_href, 'POST').then(() => {
            reload();
            void loadSetup();
        });

    const confirm = () =>
        busy.run((done) => {
            sendJson(twoFactor.confirm_href, 'POST', { code })
                .then((result) => {
                    setErrors(result.errors);

                    if (result.ok) {
                        setQr(null);
                        setCode('');
                        reload();
                        void loadCodes();
                    }
                })
                .finally(done);
        });

    const disable = () =>
        sendJson(twoFactor.disable_href, 'DELETE').then(() => {
            setCodes(null);
            reload();
        });

    const regenerate = () =>
        sendJson(twoFactor.regenerate_href, 'POST').then(() => loadCodes());

    return (
        <section className="flex flex-col gap-5 rounded-xl border border-border bg-card p-6">
            <CardHeading
                title={t('account.two_factor')}
                subtitle={t('account.two_factor_text')}
                action={
                    twoFactor.state === 'on' ? (
                        <StatusBadge tone="success" label={t('account.on')} />
                    ) : twoFactor.state === 'pending' ? (
                        <StatusBadge
                            tone="warning"
                            label={t('account.pending')}
                        />
                    ) : (
                        <StatusBadge label={t('account.off')} />
                    )
                }
            />

            {askPassword ? (
                <form
                    noValidate
                    className="flex flex-col gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        sendPassword();
                    }}
                >
                    <FormField
                        name="confirm_password"
                        label={t('auth.confirm_desc')}
                        error={errors.password}
                    >
                        <PasswordInput
                            id="confirm_password"
                            autoComplete="current-password"
                            autoFocus
                            value={password}
                            onChange={(event) =>
                                setPassword(event.target.value)
                            }
                            aria-invalid={errors.password ? true : undefined}
                            aria-describedby={fieldMessageId(
                                'confirm_password',
                            )}
                        />
                    </FormField>
                    <div className="flex gap-3">
                        <Button type="submit" loading={busy.pending}>
                            {t('auth.confirm_submit')}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setAskPassword(null)}
                        >
                            {t('form.cancel')}
                        </Button>
                    </div>
                </form>
            ) : twoFactor.state === 'off' ? (
                <div>
                    <Button
                        loading={busy.pending}
                        onClick={() => confirmed(() => void enable())}
                    >
                        <ShieldCheck />
                        {t('account.enable')}
                    </Button>
                </div>
            ) : twoFactor.state === 'pending' ? (
                <div className="flex flex-col gap-4">
                    {qr ? (
                        <div className="flex flex-wrap items-center gap-6">
                            <img
                                src={`data:image/svg+xml;utf8,${encodeURIComponent(qr.svg)}`}
                                alt={t('account.qr_alt')}
                                className="size-44 rounded-xl border border-border bg-card p-2"
                            />
                            <div className="flex min-w-0 flex-col gap-1">
                                <span className="text-body text-muted-foreground">
                                    {t('account.secret')}
                                </span>
                                <code className="font-mono text-table break-all">
                                    {qr.secret}
                                </code>
                            </div>
                        </div>
                    ) : (
                        <div>
                            <Button
                                variant="outline"
                                onClick={() =>
                                    confirmed(() => void loadSetup())
                                }
                            >
                                {t('account.show_qr')}
                            </Button>
                        </div>
                    )}
                    <form
                        noValidate
                        className="flex flex-wrap items-end gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            confirm();
                        }}
                    >
                        <FormField
                            name="two_factor_setup_code"
                            label={t('auth.two_factor_code')}
                            error={errors.code}
                        >
                            <Input
                                id="two_factor_setup_code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                value={code}
                                onChange={(event) =>
                                    setCode(event.target.value)
                                }
                                aria-invalid={errors.code ? true : undefined}
                                aria-describedby={fieldMessageId(
                                    'two_factor_setup_code',
                                )}
                                className="w-44 tracking-[0.3em] tabular-nums"
                            />
                        </FormField>
                        <Button type="submit" loading={busy.pending}>
                            {t('account.confirm_code')}
                        </Button>
                    </form>
                </div>
            ) : (
                <div className="flex flex-col gap-4">
                    {codes && (
                        <div className="flex flex-col gap-2 rounded-xl bg-muted p-4">
                            <span className="text-body text-muted-foreground">
                                {t('account.recovery_text')}
                            </span>
                            <ul className="m-0 grid list-none gap-1 p-0 font-mono text-table sm:grid-cols-2">
                                {codes.map((recovery) => (
                                    <li key={recovery}>{recovery}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <div className="flex flex-wrap gap-3">
                        <Button
                            variant="outline"
                            onClick={() =>
                                confirmed(
                                    () =>
                                        void (codes
                                            ? regenerate()
                                            : loadCodes()),
                                )
                            }
                        >
                            {t(
                                codes
                                    ? 'account.regenerate'
                                    : 'account.show_codes',
                            )}
                        </Button>
                        {!twoFactor.required && (
                            <Button
                                variant="ghost"
                                className="bg-destructive/10 text-destructive hover:bg-destructive/15 hover:text-destructive"
                                onClick={() => confirmed(() => void disable())}
                            >
                                {t('account.disable')}
                            </Button>
                        )}
                    </div>
                </div>
            )}
        </section>
    );
}

/**
 * The user's signed-in browsers (database sessions) and "Keluar dari
 * perangkat lain", which asks for the current password.
 */
export function SessionsCard({
    sessions,
    href,
}: {
    sessions: Yuan.AdminCore.Data.Account.SessionData[] | null;
    href: string;
}) {
    const t = useT();
    const { app } = usePage().props;
    const signing = useAction();
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | undefined>();

    const signOut = () =>
        signing.run((done) =>
            router.delete(href, {
                data: { password },
                preserveScroll: true,
                onError: (errors) => setError(errors.password),
                onSuccess: () => {
                    setPassword('');
                    setError(undefined);
                },
                onFinish: done,
            }),
        );

    return (
        <section className="flex flex-col gap-5 rounded-xl border border-border bg-card p-6">
            <CardHeading
                title={t('account.devices')}
                subtitle={t('account.devices_text')}
            />
            {sessions && (
                <ul className="m-0 flex list-none flex-col p-0">
                    {sessions.map((session, index) => {
                        const Icon = /iOS|Android/.test(session.agent)
                            ? Smartphone
                            : Laptop;

                        return (
                            <li
                                key={`${session.last_active}-${index}`}
                                className="flex items-center gap-3 border-b border-border py-3 last:border-0"
                            >
                                <span
                                    aria-hidden="true"
                                    className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted"
                                >
                                    <Icon className="size-4.5" />
                                </span>
                                <span className="flex min-w-0 flex-1 flex-col">
                                    <span className="truncate font-medium">
                                        {session.agent}
                                    </span>
                                    <span className="truncate text-table text-muted-foreground">
                                        {[
                                            session.ip,
                                            formatDateTime(
                                                session.last_active,
                                                app.timezone,
                                            ),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </span>
                                </span>
                                {session.current && (
                                    <StatusBadge
                                        tone="success"
                                        label={t('account.this_device')}
                                    />
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
            <form
                noValidate
                className="flex flex-col gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    signOut();
                }}
            >
                <FormField
                    name="sign_out_password"
                    label={t('auth.current_password')}
                    error={error}
                >
                    <PasswordInput
                        id="sign_out_password"
                        autoComplete="current-password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        aria-invalid={error ? true : undefined}
                        aria-describedby={fieldMessageId('sign_out_password')}
                    />
                </FormField>
                <div>
                    <Button type="submit" loading={signing.pending}>
                        <LogOut />
                        {t('account.sign_out_others')}
                    </Button>
                </div>
            </form>
        </section>
    );
}
