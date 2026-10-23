import { FormFooter } from '@admin-core/components/admin/FormFooter';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { UnsavedChangesGuard } from '@admin-core/components/admin/UnsavedChangesGuard';
import { useAdminForm } from '@admin-core/hooks/useAdminForm';
import { useT } from '@admin-core/lib/i18n';
import { Head } from '@inertiajs/react';
import { index, store } from '@/routes/levels';
import { LevelForm } from '../components/LevelForm';
import type { LevelFormValues } from '../components/LevelForm';

export default function Create() {
    const t = useT();
    const { form, submit } = useAdminForm<LevelFormValues>({
        label: '',
        description: '',
    });

    return (
        <>
            <Head title={t('levels.create_title')} />
            <form
                noValidate
                className="flex flex-col gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(store());
                }}
            >
                <PageHeader
                    title={t('levels.create_title')}
                    backHref={index()}
                />
                <LevelForm form={form} />
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
