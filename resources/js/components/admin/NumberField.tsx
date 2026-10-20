import { useState } from 'react';
import { formatNumber, parseNumber } from '../../lib/format';
import { Input } from '../ui/input';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<number | null> & {
    /** `Rp` in front, whole rupiah only. */
    currency?: boolean;
    maximumFractionDigits?: number;
};

/**
 * Locale-aware number entry (frontend-behavior §6): shows `1.250.000,5`,
 * keeps a plain number, and takes pasted text in either notation. The text
 * is reformatted when the field loses focus.
 */
export function NumberField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    currency = false,
    maximumFractionDigits = 2,
}: Props) {
    const digits = currency ? 0 : maximumFractionDigits;
    const show = (number: number | null) =>
        number === null ? '' : formatNumber(number, digits);
    const [text, setText] = useState(() => show(value));

    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <div className="relative">
                {currency && (
                    <span
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-body text-muted-foreground"
                    >
                        Rp
                    </span>
                )}
                <Input
                    {...controlProps({ name, error, required, disabled })}
                    inputMode={digits > 0 ? 'decimal' : 'numeric'}
                    autoComplete="off"
                    value={text}
                    className={
                        currency
                            ? 'pl-10 text-right tabular-nums'
                            : 'text-right tabular-nums'
                    }
                    onChange={(event) => {
                        setText(event.target.value);
                        const parsed = parseNumber(event.target.value);
                        onChange(
                            parsed === null
                                ? null
                                : Number(parsed.toFixed(digits)),
                        );
                    }}
                    onBlur={() => setText(show(value))}
                />
            </div>
        </FormField>
    );
}

/** Rupiah: `NumberField` with the `Rp` prefix and no decimals. */
export function CurrencyField(props: FieldProps<number | null>) {
    return <NumberField {...props} currency />;
}
