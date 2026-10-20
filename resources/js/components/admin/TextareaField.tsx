import { cn } from '../../lib/utils';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

/** Multi-line text; Enter makes a new line and never submits the form. */
export function TextareaField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    rows = 4,
}: FieldProps<string> & { rows?: number }) {
    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <textarea
                {...controlProps({ name, error, required, disabled })}
                rows={rows}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className={cn(
                    'w-full min-w-0 rounded border border-input bg-transparent px-3 py-2 text-body placeholder:text-muted-foreground disabled:opacity-50 aria-invalid:border-destructive',
                )}
            />
        </FormField>
    );
}
