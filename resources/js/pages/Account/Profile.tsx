import { Head, Link, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { DetailGrid } from '../../components/admin/DetailGrid';
import { FormFooter } from '../../components/admin/FormFooter';
import { PageHeader } from '../../components/admin/PageHeader';
import { StaleUpdateDialog } from '../../components/admin/StaleUpdateDialog';
import { TextField } from '../../components/admin/TextField';
import { UnsavedChangesGuard } from '../../components/admin/UnsavedChangesGuard';
import { Button } from '../../components/ui/button';
import { useAdminForm } from '../../hooks/useAdminForm';
import { useT } from '../../lib/i18n';

/**
 * The signed-in user's own name and email (derived design: the approved form
 * pattern in one card). Username and level are shown; an admin changes them.
 */
export default function Profile({
    profile,
}: {
    profile: Yuan.AdminCore.Data.Account.ProfileData;
}) {
    const t = useT();
    const { app } = usePage().props;
    const { form, submit, stale, dirtyData, reload } = useAdminForm({
        name: profile.name,
        email: profile.email,
        lock_version: profile.lock_version,
    });

    return (
        <>
            <Head title={t('profile.title')} />
            <form
                noValidate
                className="flex flex-col gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit({ url: profile.submit_href, method: 'put' });
                }}
            >
                <PageHeader
                    title={t('profile.title')}
                    description={t('profile.description')}
                    actions={
                        <Button
                            variant="outline"
                            asChild
                            className="bg-transparent"
                        >
                            <Link href={profile.password_href}>
                                <KeyRound />
                                {t('shell.change_password')}
                            </Link>
                        </Button>
                    }
                />
                <section className="flex flex-col gap-5 rounded bg-card p-6 text-card-foreground">
                    <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                        <TextField
                            name="name"
                            label={t('profile.name')}
                            required
                            autoComplete="name"
                            value={form.data.name}
                            onChange={(value) => form.setData('name', value)}
                            error={form.errors.name}
                        />
                        <TextField
                            name="email"
                            label={t('profile.email')}
                            required
                            type="email"
                            autoComplete="email"
                            value={form.data.email}
                            onChange={(value) => form.setData('email', value)}
                            error={form.errors.email}
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
                </section>
                <FormFooter
                    cancelHref={profile.submit_href}
                    processing={form.processing}
                    dirty={form.isDirty}
                />
            </form>
            <UnsavedChangesGuard
                when={form.isDirty && !form.processing && !stale}
            />
            <StaleUpdateDialog
                open={stale}
                dirtyData={dirtyData}
                onReload={reload}
            />
        </>
    );
}
