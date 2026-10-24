import { FormFooter } from '@admin-core/components/admin/FormFooter';
import { ImageField } from '@admin-core/components/admin/ImageField';
import { NumberField } from '@admin-core/components/admin/NumberField';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { SelectField } from '@admin-core/components/admin/SelectField';
import { StaleUpdateDialog } from '@admin-core/components/admin/StaleUpdateDialog';
import { SwitchField } from '@admin-core/components/admin/SwitchField';
import { Tabs } from '@admin-core/components/admin/Tabs';
import { TextareaField } from '@admin-core/components/admin/TextareaField';
import { TextField } from '@admin-core/components/admin/TextField';
import { UnsavedChangesGuard } from '@admin-core/components/admin/UnsavedChangesGuard';
import { useAdminForm } from '@admin-core/hooks/useAdminForm';
import { useT } from '@admin-core/lib/i18n';
import { Head, router } from '@inertiajs/react';
import { index, update } from '@/routes/settings';

type Tab = Yuan.AdminCore.Modules.Settings.Data.SettingsTabData;
type Field = Yuan.AdminCore.Modules.Settings.Data.SettingFieldData;
type Value = string | number | boolean | File | null;

const INPUT_MODE: Partial<Record<Field['type'], 'tel' | 'numeric' | 'url'>> = {
    phone: 'tel',
    whatsapp: 'tel',
    postal_code: 'numeric',
    social: 'url',
};

/**
 * Pengaturan (derived: approved pattern-form inside the Show tabs): one form
 * per tab, rendered from the settings registry. Changing tab is a visit
 * (`?tab=`), so unsaved changes are guarded like any other navigation.
 */
export default function Index({
    tabs,
    tab,
}: Yuan.AdminCore.Modules.Settings.Data.SettingsPageData) {
    const t = useT();
    const active = tabs.find((item) => item.key === tab) ?? tabs[0];

    return (
        <>
            <Head title={t('settings.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('settings.title')}
                    description={t('settings.description')}
                />
                <Tabs
                    label={t('settings.tabs')}
                    value={active.key}
                    onChange={(key) =>
                        router.get(
                            index({ query: { tab: key } }),
                            {},
                            { preserveScroll: true },
                        )
                    }
                    tabs={tabs.map((item) => ({
                        key: item.key,
                        label: item.label,
                        content:
                            item.key === active.key ? (
                                <SettingsForm key={item.key} tab={item} />
                            ) : null,
                    }))}
                />
            </div>
        </>
    );
}

function SettingsForm({ tab }: { tab: Tab }) {
    const { form, submit, stale, dirtyData, reload } = useAdminForm<{
        values: Record<string, Value>;
        lock_version: number;
    }>({
        values: Object.fromEntries(
            tab.fields.map((field) => [
                field.name,
                field.type === 'image' ? null : field.value,
            ]),
        ),
        lock_version: tab.lock_version,
    });
    const set = (name: string, value: Value) =>
        form.setData('values', { ...form.data.values, [name]: value });
    const errors = form.errors as Record<string, string | undefined>;

    return (
        <form
            noValidate
            className="flex flex-col gap-5"
            onSubmit={(event) => {
                event.preventDefault();
                submit({ url: update.form(tab.key).action, method: 'post' });
            }}
        >
            <section className="flex max-w-2xl flex-col gap-5 rounded bg-card p-6 text-card-foreground">
                {tab.fields.map((field) => (
                    <SettingInput
                        key={field.name}
                        field={field}
                        value={form.data.values[field.name] ?? null}
                        error={errors[`values.${field.name}`]}
                        onChange={(value) => set(field.name, value)}
                    />
                ))}
            </section>
            <FormFooter
                cancelHref={index({ query: { tab: tab.key } })}
                processing={form.processing}
                dirty={form.isDirty}
            />
            <UnsavedChangesGuard
                when={form.isDirty && !form.processing && !stale}
            />
            <StaleUpdateDialog
                open={stale}
                dirtyData={dirtyData}
                onReload={reload}
            />
        </form>
    );
}

function SettingInput({
    field,
    value,
    error,
    onChange,
}: {
    field: Field;
    value: Value;
    error?: string;
    onChange: (value: Value) => void;
}) {
    const common = {
        name: `values.${field.name}`,
        label: field.label,
        help: field.help ?? undefined,
        error,
    };

    switch (field.type) {
        case 'text':
            return (
                <TextareaField
                    {...common}
                    value={typeof value === 'string' ? value : ''}
                    onChange={onChange}
                />
            );
        case 'integer':
            return (
                <NumberField
                    {...common}
                    value={typeof value === 'number' ? value : null}
                    onChange={onChange}
                />
            );
        case 'boolean':
            return (
                <SwitchField
                    {...common}
                    value={value === true}
                    onChange={onChange}
                />
            );
        case 'enum':
            return (
                <SelectField
                    {...common}
                    required
                    options={field.options}
                    value={typeof value === 'string' ? value : ''}
                    onChange={onChange}
                />
            );
        case 'image':
            return (
                <ImageField
                    {...common}
                    current={
                        typeof field.value === 'string' ? field.value : null
                    }
                    accept={field.accept}
                    value={value instanceof File ? value : null}
                    onChange={onChange}
                />
            );
        default:
            return (
                <TextField
                    {...common}
                    inputMode={INPUT_MODE[field.type]}
                    value={typeof value === 'string' ? value : ''}
                    onChange={onChange}
                />
            );
    }
}
