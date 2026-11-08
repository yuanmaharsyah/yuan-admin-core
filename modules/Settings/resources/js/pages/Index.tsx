import type { LucideIcon } from 'lucide-react';
import {
    Download,
    Globe,
    LockKeyhole,
    Phone,
    Settings2,
    Share2,
} from 'lucide-react';
import { CardHeading } from '@admin-core/components/admin/CardHeading';
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
/** The tab bar's icons, by registry group (a project's own group falls back to the gear). */
const ICONS: Record<string, LucideIcon> = {
    umum: Settings2,
    kontak: Phone,
    sosial: Share2,
    akses: Globe,
    keamanan: LockKeyhole,
    ekspor: Download,
};

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
                    variant="segmented"
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
                        icon: ICONS[item.key] ?? Settings2,
                        content:
                            item.key === active.key ? (
                                // A save redirects back here: the new lock_version
                                // remounts the form from the saved values, so it
                                // isn't dirty and its next save isn't stale.
                                <SettingsForm
                                    key={`${item.key}:${item.lock_version}`}
                                    tab={item}
                                />
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
            <section className="mx-auto flex w-full max-w-4xl flex-col gap-6 rounded-xl border border-border bg-card p-6 text-card-foreground">
                <CardHeading title={tab.label} />
                <div className="grid gap-x-6 gap-y-5 md:grid-cols-2">
                    {tab.fields.map((field) => (
                        <div
                            key={field.name}
                            className={
                                field.type === 'image' || field.type === 'text'
                                    ? 'md:col-span-2'
                                    : undefined
                            }
                        >
                            <SettingInput
                                field={field}
                                value={form.data.values[field.name] ?? null}
                                error={errors[`values.${field.name}`]}
                                onChange={(value) => set(field.name, value)}
                            />
                        </div>
                    ))}
                </div>
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
