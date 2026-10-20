import { ChevronDown } from 'lucide-react';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<string> & {
    options: Yuan.AdminCore.Data.OptionData[];
    /** Shown while nothing is chosen. */
    placeholder?: string;
};

/** A short fixed list: the native select (keyboard and mobile pickers for free). */
export function SelectField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    options,
    placeholder,
}: Props) {
    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <div className="relative">
                <select
                    {...controlProps({ name, error, required, disabled })}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-control w-full appearance-none rounded border border-input bg-transparent pr-9 pl-3 text-body disabled:opacity-50 aria-invalid:border-destructive [&>option]:bg-popover"
                >
                    {placeholder !== undefined && (
                        <option value="">{placeholder}</option>
                    )}
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <ChevronDown
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
            </div>
        </FormField>
    );
}
