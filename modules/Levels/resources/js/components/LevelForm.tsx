import { TextareaField } from '@admin-core/components/admin/TextareaField';
import { TextField } from '@admin-core/components/admin/TextField';
import { useT } from '@admin-core/lib/i18n';
import type { InertiaFormProps } from '@inertiajs/react';

export type LevelFormValues = Omit<
    Yuan.AdminCore.Modules.Levels.Data.StoreLevelData,
    'description'
> & { description: string };

/** The two fields of a level (derived from the approved form pattern). */
export function LevelForm({
    form,
}: {
    form: Pick<
        InertiaFormProps<LevelFormValues>,
        'data' | 'errors' | 'setData'
    >;
}) {
    const t = useT();

    return (
        <section className="flex flex-col gap-4 rounded bg-card p-6 text-card-foreground">
            <TextField
                name="label"
                label={t('levels.name')}
                required
                maxLength={60}
                value={form.data.label}
                onChange={(value) => form.setData('label', value)}
                error={form.errors.label}
            />
            <TextareaField
                name="description"
                label={t('levels.desc')}
                rows={3}
                value={form.data.description}
                onChange={(value) => form.setData('description', value)}
                error={form.errors.description}
            />
        </section>
    );
}
