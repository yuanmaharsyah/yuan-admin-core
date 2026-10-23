import { FormErrorSummary } from '@admin-core/components/admin/FormErrorSummary';
import {
    FormField,
    fieldMessageId,
} from '@admin-core/components/admin/FormField';
import { NumberField } from '@admin-core/components/admin/NumberField';
import { PasswordInput } from '@admin-core/components/admin/PasswordInput';
import { SelectField } from '@admin-core/components/admin/SelectField';
import { SwitchField } from '@admin-core/components/admin/SwitchField';
import { TextField } from '@admin-core/components/admin/TextField';
import { useT } from '@admin-core/lib/i18n';
import type { InertiaFormProps } from '@inertiajs/react';
import type { ReactNode } from 'react';

type Store = Yuan.AdminCore.Modules.Users.Data.StoreUserData;

/**
 * The form's state, from the store Data: text fields empty instead of null,
 * the level empty until chosen, the password only on create.
 */
export type UserFormValues = Omit<
    Store,
    'username' | 'level_id' | 'password'
> & {
    username: string;
    level_id: number | null;
    password?: string;
    password_confirmation?: string;
};

type Props = {
    form: Pick<InertiaFormProps<UserFormValues>, 'data' | 'errors' | 'setData'>;
    options: Yuan.AdminCore.Modules.Users.Data.UserFormOptionsData;
    /** Create only: shows the password fields with this hint. */
    passwordHint?: string;
};

/** Fields of the user create and edit pages; the project's extra fields come last. */
export function UserForm({ form, options, passwordHint }: Props) {
    const t = useT();
    const { data, errors } = form;
    const extraError = (name: string) =>
        (errors as Record<string, string | undefined>)[`extra.${name}`];
    const setExtra = (name: string, value: string | number | null) =>
        form.setData('extra', { ...data.extra, [name]: value });

    return (
        <div className="flex flex-col gap-5">
            <FormErrorSummary errors={errors} />
            <Section title={t('users.section_account')}>
                <TextField
                    name="name"
                    label={t('users.name')}
                    required
                    autoComplete="off"
                    value={data.name}
                    onChange={(value) => form.setData('name', value)}
                    error={errors.name}
                />
                <TextField
                    name="username"
                    label={t('users.username')}
                    help={t('users.username_help')}
                    autoComplete="off"
                    value={data.username}
                    onChange={(value) => form.setData('username', value)}
                    error={errors.username}
                />
                <TextField
                    name="email"
                    label={t('users.email')}
                    required
                    type="email"
                    autoComplete="off"
                    value={data.email}
                    onChange={(value) => form.setData('email', value)}
                    error={errors.email}
                />
                {passwordHint !== undefined && (
                    <>
                        <PasswordField
                            name="password"
                            label={t('users.password')}
                            help={`${passwordHint} ${t('users.password_note')}`}
                            value={data.password ?? ''}
                            error={errors.password}
                            onChange={(value) =>
                                form.setData('password', value)
                            }
                        />
                        <PasswordField
                            name="password_confirmation"
                            label={t('users.password_confirmation')}
                            value={data.password_confirmation ?? ''}
                            error={errors.password_confirmation}
                            onChange={(value) =>
                                form.setData('password_confirmation', value)
                            }
                        />
                    </>
                )}
            </Section>
            <Section title={t('users.section_access')}>
                <SelectField
                    name="level_id"
                    label={t('users.level')}
                    required
                    value={data.level_id === null ? '' : String(data.level_id)}
                    onChange={(value) =>
                        form.setData(
                            'level_id',
                            value === '' ? null : Number(value),
                        )
                    }
                    options={options.levels}
                    placeholder=""
                    error={errors.level_id}
                />
                <div className="flex items-end pb-2">
                    <SwitchField
                        name="is_active"
                        label={t('users.active_switch')}
                        value={data.is_active}
                        onChange={(value) => form.setData('is_active', value)}
                        error={errors.is_active}
                    />
                </div>
            </Section>
            {options.extra_fields.length > 0 && (
                <Section title={t('users.section_extra')}>
                    {options.extra_fields.map((field) => {
                        const common = {
                            name: `extra.${field.name}`,
                            label: field.label,
                            required: field.required,
                            help: field.help ?? undefined,
                            error: extraError(field.name),
                        };
                        const value = data.extra[field.name] ?? null;

                        if (field.type === 'select') {
                            return (
                                <SelectField
                                    key={field.name}
                                    {...common}
                                    value={value === null ? '' : String(value)}
                                    onChange={(next) =>
                                        setExtra(
                                            field.name,
                                            next === '' ? null : next,
                                        )
                                    }
                                    options={field.options}
                                    placeholder=""
                                />
                            );
                        }

                        if (field.type === 'number') {
                            return (
                                <NumberField
                                    key={field.name}
                                    {...common}
                                    value={
                                        typeof value === 'number'
                                            ? value
                                            : value === null
                                              ? null
                                              : Number(value)
                                    }
                                    onChange={(next) =>
                                        setExtra(field.name, next)
                                    }
                                />
                            );
                        }

                        return (
                            <TextField
                                key={field.name}
                                {...common}
                                value={value === null ? '' : String(value)}
                                onChange={(next) =>
                                    setExtra(
                                        field.name,
                                        next === '' ? null : next,
                                    )
                                }
                            />
                        );
                    })}
                </Section>
            )}
        </div>
    );
}

function PasswordField({
    name,
    label,
    help,
    value,
    error,
    onChange,
}: {
    name: string;
    label: string;
    help?: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    return (
        <FormField name={name} label={label} required help={help} error={error}>
            <PasswordInput
                id={name}
                name={name}
                autoComplete="new-password"
                required
                value={value}
                onChange={(event) => onChange(event.target.value)}
                aria-invalid={error ? true : undefined}
                aria-describedby={fieldMessageId(name)}
            />
        </FormField>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-4 rounded bg-card p-6 text-card-foreground">
            <h2 className="m-0 text-section">{title}</h2>
            <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                {children}
            </div>
        </section>
    );
}
