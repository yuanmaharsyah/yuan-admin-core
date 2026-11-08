import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Bell,
    CircleCheck,
    Clock,
    LockKeyhole,
    Mail,
    Newspaper,
    Truck,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import {
    SessionsCard,
    TwoFactorCard,
} from '../../components/admin/AccountSecurity';
import { CardHeading } from '../../components/admin/CardHeading';
import { DetailGrid } from '../../components/admin/DetailGrid';
import { fieldMessageId, FormField } from '../../components/admin/FormField';
import { ImageField } from '../../components/admin/ImageField';
import { PageHeader } from '../../components/admin/PageHeader';
import { PasswordInput } from '../../components/admin/PasswordInput';
import { StaleUpdateDialog } from '../../components/admin/StaleUpdateDialog';
import { Tabs } from '../../components/admin/Tabs';
import { TextField } from '../../components/admin/TextField';
import { UnsavedChangesGuard } from '../../components/admin/UnsavedChangesGuard';
import { Button } from '../../components/ui/button';
import { Switch } from '../../components/ui/switch';
import { useAction } from '../../hooks/useAction';
import { useAdminForm } from '../../hooks/useAdminForm';
import { useT } from '../../lib/i18n';

type Account = Yuan.AdminCore.Data.Account.AccountPageData;

/**
 * Akun (restyle 03, the demo's "Account Setting" layout): Profil, Notifikasi
 * and Keamanan tabs; the tab is in `?tab=`.
 */
export default function Settings({ account }: { account: Account }) {
    const t = useT();
    const [tab, setTab] = useState<string>(account.tab);
    const { flash } = usePage();
    const status =
        typeof flash === 'object' && flash !== null && 'status' in flash
            ? String(flash.status)
            : null;

    const choose = (key: string) => {
        setTab(key);
        window.history.replaceState(window.history.state, '', `?tab=${key}`);
    };

    return (
        <>
            <Head title={t('account.title')} />
            <div className="flex flex-col gap-6">
                <PageHeader
                    title={t('account.title')}
                    description={t('account.description')}
                />
                {status && (
                    <p className="m-0 rounded-xl bg-warning/10 px-4 py-3 text-warning">
                        {status}
                    </p>
                )}
                <Tabs
                    label={t('account.title')}
                    variant="segmented"
                    value={tab}
                    onChange={choose}
                    tabs={[
                        {
                            key: 'profil',
                            label: t('account.tab_profile'),
                            icon: UserRound,
                            content: <ProfileTab account={account} />,
                        },
                        {
                            key: 'notifikasi',
                            label: t('account.tab_notifications'),
                            icon: Bell,
                            content: <NotificationsTab />,
                        },
                        {
                            key: 'keamanan',
                            label: t('account.tab_security'),
                            icon: LockKeyhole,
                            content: (
                                <div className="grid items-start gap-6 xl:grid-cols-[3fr_2fr]">
                                    {account.two_factor ? (
                                        <TwoFactorCard
                                            twoFactor={account.two_factor}
                                        />
                                    ) : (
                                        <section className="rounded-xl border border-border bg-card p-6">
                                            <CardHeading
                                                title={t('account.two_factor')}
                                                subtitle={t(
                                                    'account.two_factor_unavailable',
                                                )}
                                            />
                                        </section>
                                    )}
                                    <SessionsCard
                                        sessions={account.sessions}
                                        href={account.sign_out_others_href}
                                    />
                                </div>
                            ),
                        },
                    ]}
                />
            </div>
        </>
    );
}

function ProfileTab({ account }: { account: Account }) {
    const t = useT();
    const { app } = usePage().props;
    const profile = account.profile;
    const [photo, setPhoto] = useState<File | null>(null);
    const uploading = useAction();
    const removing = useAction();
    const details = useAdminForm({
        name: profile.name,
        email: profile.email,
        lock_version: profile.lock_version,
    });
    const password = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const changing = useAction();

    return (
        <div className="flex flex-col gap-6">
            <div className="grid items-start gap-6 lg:grid-cols-2">
                <section className="flex flex-col gap-5 rounded-xl border border-border bg-card p-6">
                    <CardHeading
                        title={t('account.photo')}
                        subtitle={t('account.photo_text')}
                    />
                    <ImageField
                        name="avatar"
                        label={t('account.photo')}
                        value={photo}
                        onChange={setPhoto}
                        current={profile.avatar_url}
                        accept={['jpg', 'jpeg', 'png', 'webp']}
                        help={t('account.photo_rules')}
                        round
                    />
                    <div className="flex flex-wrap gap-3">
                        {photo && (
                            <Button
                                loading={uploading.pending}
                                onClick={() =>
                                    uploading.run((done) =>
                                        router.post(
                                            profile.avatar_submit_href,
                                            { avatar: photo },
                                            {
                                                forceFormData: true,
                                                preserveScroll: true,
                                                onSuccess: () => setPhoto(null),
                                                onFinish: done,
                                            },
                                        ),
                                    )
                                }
                            >
                                {t('account.photo_save')}
                            </Button>
                        )}
                        {profile.avatar_destroy_href && !photo && (
                            <Button
                                variant="ghost"
                                loading={removing.pending}
                                className="bg-destructive/10 text-destructive hover:bg-destructive/15 hover:text-destructive"
                                onClick={() => {
                                    const href = profile.avatar_destroy_href;

                                    if (href) {
                                        removing.run((done) =>
                                            router.delete(href, {
                                                preserveScroll: true,
                                                onFinish: done,
                                            }),
                                        );
                                    }
                                }}
                            >
                                {t('account.photo_remove')}
                            </Button>
                        )}
                    </div>
                </section>

                <form
                    noValidate
                    className="flex flex-col gap-5 rounded-xl border border-border bg-card p-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        changing.run((done) =>
                            password.put(account.password_submit_href, {
                                preserveScroll: true,
                                onSuccess: () => password.reset(),
                                onFinish: done,
                            }),
                        );
                    }}
                >
                    <CardHeading
                        title={t('auth.change_title')}
                        subtitle={t('account.password_text')}
                    />
                    {(
                        [
                            'current_password',
                            'password',
                            'password_confirmation',
                        ] as const
                    ).map((field) => (
                        <FormField
                            key={field}
                            name={field}
                            label={t(
                                field === 'current_password'
                                    ? 'auth.current_password'
                                    : field === 'password'
                                      ? 'auth.new_password'
                                      : 'auth.confirm_new_password',
                            )}
                            help={
                                field === 'password'
                                    ? account.password_hint
                                    : undefined
                            }
                            error={password.errors[field]}
                        >
                            <PasswordInput
                                id={field}
                                autoComplete={
                                    field === 'current_password'
                                        ? 'current-password'
                                        : 'new-password'
                                }
                                value={password.data[field]}
                                onChange={(event) =>
                                    password.setData(field, event.target.value)
                                }
                                aria-invalid={
                                    password.errors[field] ? true : undefined
                                }
                                aria-describedby={fieldMessageId(field)}
                            />
                        </FormField>
                    ))}
                    <div>
                        <Button type="submit" loading={changing.pending}>
                            {t('auth.save_password')}
                        </Button>
                    </div>
                </form>
            </div>

            <form
                noValidate
                className="flex flex-col gap-5 rounded-xl border border-border bg-card p-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    details.submit({ url: profile.submit_href, method: 'put' });
                }}
            >
                <CardHeading
                    title={t('account.details')}
                    subtitle={t('account.details_text')}
                />
                <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                    <TextField
                        name="name"
                        label={t('profile.name')}
                        required
                        autoComplete="name"
                        value={details.form.data.name}
                        onChange={(value) =>
                            details.form.setData('name', value)
                        }
                        error={details.form.errors.name}
                    />
                    <TextField
                        name="email"
                        label={t('profile.email')}
                        required
                        type="email"
                        autoComplete="email"
                        value={details.form.data.email}
                        onChange={(value) =>
                            details.form.setData('email', value)
                        }
                        error={details.form.errors.email}
                    />
                </div>
                <DetailGrid
                    items={[
                        {
                            label: t('profile.username'),
                            value: profile.username,
                        },
                        {
                            label: t('profile.level'),
                            value: profile.level_label,
                        },
                    ]}
                />
                <p className="m-0 text-table text-muted-foreground">
                    {t('profile.admin_only', { app: app.name })}
                </p>
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        loading={details.form.processing}
                        disabled={!details.form.isDirty}
                    >
                        {t('form.save')}
                    </Button>
                </div>
            </form>
            <UnsavedChangesGuard
                when={
                    details.form.isDirty &&
                    !details.form.processing &&
                    !details.stale
                }
            />
            <StaleUpdateDialog
                open={details.stale}
                dirtyData={details.dirtyData}
                onReload={details.reload}
            />
        </div>
    );
}

/**
 * Reserved room (owner's decision, restyle 03): the demo's notification
 * preferences. The switches work on this page only and save nothing until a
 * notification system exists; the card says so.
 */
function NotificationsTab() {
    const t = useT();
    const rows = [
        { key: 'newsletter', icon: Newspaper, on: false },
        { key: 'confirmation', icon: CircleCheck, on: true },
        { key: 'status', icon: Clock, on: true },
        { key: 'delivered', icon: Truck, on: false },
        { key: 'email', icon: Mail, on: true },
    ] as const;
    const [on, setOn] = useState<Record<string, boolean>>(
        Object.fromEntries(rows.map((row) => [row.key, row.on])),
    );

    return (
        <section className="mx-auto flex w-full max-w-4xl flex-col gap-5 rounded-xl border border-border bg-card p-6">
            <CardHeading
                title={t('account.notifications')}
                subtitle={t('account.notifications_text')}
            />
            <ul className="m-0 flex list-none flex-col gap-1 p-0">
                {rows.map((row) => (
                    <li key={row.key} className="flex items-center gap-4 py-3">
                        <span
                            aria-hidden="true"
                            className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-muted"
                        >
                            <row.icon className="size-5" />
                        </span>
                        <span className="flex min-w-0 flex-1 flex-col">
                            <span
                                id={`notif-${row.key}`}
                                className="font-medium"
                            >
                                {t(`account.notif_${row.key}`)}
                            </span>
                            <span className="text-table text-muted-foreground">
                                {t(`account.notif_${row.key}_text`)}
                            </span>
                        </span>
                        <Switch
                            aria-labelledby={`notif-${row.key}`}
                            checked={on[row.key]}
                            onCheckedChange={(checked) =>
                                setOn({ ...on, [row.key]: checked })
                            }
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}
