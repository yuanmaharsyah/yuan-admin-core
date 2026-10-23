import { FormFooter } from '@admin-core/components/admin/FormFooter';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { UnsavedChangesGuard } from '@admin-core/components/admin/UnsavedChangesGuard';
import { useAdminForm } from '@admin-core/hooks/useAdminForm';
import { useT } from '@admin-core/lib/i18n';
import { Head } from '@inertiajs/react';
import { index, store } from '@/routes/users';
import { UserForm } from '../components/UserForm';
import type { UserFormValues } from '../components/UserForm';

type Props = {
    options: Yuan.AdminCore.Modules.Users.Data.UserFormOptionsData;
};

export default function Create({ options }: Props) {
    const t = useT();
    const { form, submit } = useAdminForm<UserFormValues>({
        name: '',
        username: '',
        email: '',
        level_id: null,
        is_active: true,
        extra: Object.fromEntries(
            options.extra_fields.map((field) => [field.name, null]),
        ),
        password: '',
        password_confirmation: '',
    });

    return (
        <>
            <Head title={t('users.create_title')} />
            <form
                noValidate
                className="flex flex-col gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(store());
                }}
            >
                <PageHeader
                    title={t('users.create_title')}
                    backHref={index()}
                />
                <UserForm
                    form={form}
                    options={options}
                    passwordHint={options.password_hint}
                />
                <FormFooter
                    cancelHref={index()}
                    processing={form.processing}
                    dirty={form.isDirty}
                />
            </form>
            <UnsavedChangesGuard when={form.isDirty && !form.processing} />
        </>
    );
}
