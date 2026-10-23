import { FormFooter } from '@admin-core/components/admin/FormFooter';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { StaleUpdateDialog } from '@admin-core/components/admin/StaleUpdateDialog';
import { UnsavedChangesGuard } from '@admin-core/components/admin/UnsavedChangesGuard';
import { useAdminForm } from '@admin-core/hooks/useAdminForm';
import { useT } from '@admin-core/lib/i18n';
import { Head } from '@inertiajs/react';
import { index, update } from '@/routes/levels';
import { LevelForm } from '../components/LevelForm';
import type { LevelFormValues } from '../components/LevelForm';

export default function Edit({
    level,
}: {
    level: Yuan.AdminCore.Modules.Levels.Data.LevelFormData;
}) {
    const t = useT();
    const { form, submit, stale, dirtyData, reload } = useAdminForm<
        LevelFormValues & { lock_version: number }
    >({
        label: level.label,
        description: level.description ?? '',
        lock_version: level.lock_version,
    });
    const title = t('levels.edit_title', { name: level.label });

    return (
        <>
            <Head title={title} />
            <form
                noValidate
                className="flex flex-col gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(update(level.id));
                }}
            >
                <PageHeader title={title} backHref={index()} />
                <LevelForm form={form} />
                <FormFooter
                    cancelHref={index()}
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
