import type { ComponentProps } from 'react';
import { Input } from '../ui/input';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<string> &
    Omit<ComponentProps<'input'>, 'value' | 'onChange' | 'name'>;

/** A one-line text input in a FormField. */
export function TextField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    ...input
}: Props) {
    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <Input
                {...input}
                {...controlProps({ name, error, required, disabled })}
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
        </FormField>
    );
}
