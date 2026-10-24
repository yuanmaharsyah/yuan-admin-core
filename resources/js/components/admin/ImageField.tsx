import { useEffect, useMemo } from 'react';
import { Input } from '../ui/input';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<File | null> & {
    /** The stored image's URL, shown until a new file is picked. */
    current: string | null;
    /** File extensions, e.g. ['png', 'ico']. */
    accept: string[];
};

/** One image upload with a preview (Settings → Umum branding); the file is sent with the form. */
export function ImageField({
    name,
    label,
    value,
    onChange,
    error,
    required,
    help,
    disabled,
    current,
    accept,
}: Props) {
    const preview = useMemo(
        () => (value === null ? null : URL.createObjectURL(value)),
        [value],
    );

    useEffect(
        () => () => {
            if (preview !== null) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    const shown = preview ?? current;

    return (
        <FormField
            name={name}
            label={label}
            required={required}
            help={help}
            error={error}
        >
            <div className="flex items-center gap-4">
                <div className="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded border border-border bg-muted">
                    {shown !== null && (
                        <img
                            src={shown}
                            alt=""
                            className="max-h-full max-w-full object-contain"
                        />
                    )}
                </div>
                <Input
                    {...controlProps({ name, error, required, disabled })}
                    type="file"
                    accept={accept
                        .map((extension) => `.${extension}`)
                        .join(',')}
                    onChange={(event) =>
                        onChange(event.target.files?.[0] ?? null)
                    }
                />
            </div>
        </FormField>
    );
}
