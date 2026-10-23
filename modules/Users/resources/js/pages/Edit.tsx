import { FormFooter } from '@admin-core/components/admin/FormFooter';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { StaleUpdateDialog } from '@admin-core/components/admin/StaleUpdateDialog';
import { UnsavedChangesGuard } from '@admin-core/components/admin/UnsavedChangesGuard';
import { useAdminForm } from '@admin-core/hooks/useAdminForm';
import { useT } from '@admin-core/lib/i18n';
import { Head } from '@inertiajs/react';
import { show, update } from '@/routes/users';
import { UserForm } from '../components/UserForm';
import type { UserFormValues } from '../components/UserForm';

type Props = {
    user: Yuan.AdminCore.Modules.Users.Data.UserData;
    options: Yuan.AdminCore.Modules.Users.Data.UserFormOptionsData;
};

export default function Edit({ user, options }: Props) {
    const t = useT();
    const { form, submit, stale, dirtyData, reload } = useAdminForm<
        UserFormValues & { lock_version: number }
    >({
        name: user.name,
        username: user.username ?? '',
        email: user.email,
        level_id: user.level_id,
        is_active: user.is_active,
        extra: Object.fromEntries(
            user.extra.map((field) => [field.name, field.value]),
        ),
        lock_version: user.lock_version,
    });
    const title = t('users.edit_title', { name: user.name });

    return (
        <>
            <Head title={title} />
            <form
                noValidate
                className="flex flex-col gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(update(user.id));
                }}
            >
                <PageHeader title={title} backHref={show(user.id)} />
                <UserForm form={form} options={options} />
                <FormFooter
                    cancelHref={show(user.id)}
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
