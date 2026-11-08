import { ImageIcon, Upload } from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { controlProps, FormField } from './FormField';
import type { FieldProps } from './FormField';

type Props = FieldProps<File | null> & {
    /** The stored image's URL, shown until a new file is picked. */
    current: string | null;
    /** File extensions, e.g. ['png', 'ico']. */
    accept: string[];
    /** A round preview (avatars); square by default (logos). */
    round?: boolean;
};

/**
 * One image upload with a preview (Settings branding, the account avatar):
 * the image, "Unggah" and "Reset" (drops the picked file), the help line.
 * The file is sent with the form.
 */
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
    round = false,
}: Props) {
    const t = useT();
    const input = useRef<HTMLInputElement>(null);
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
            <div className="flex flex-wrap items-center gap-5">
                <div
                    className={cn(
                        'flex size-24 shrink-0 items-center justify-center overflow-hidden border border-border bg-muted',
                        round ? 'rounded-full' : 'rounded-xl',
                    )}
                >
                    {shown !== null ? (
                        <img
                            src={shown}
                            alt=""
                            className={cn(
                                'size-full',
                                round ? 'object-cover' : 'object-contain p-2',
                            )}
                        />
                    ) : (
                        <ImageIcon
                            aria-hidden="true"
                            className="size-7 text-muted-foreground"
                        />
                    )}
                </div>
                <div className="flex gap-3">
                    <Button
                        type="button"
                        disabled={disabled}
                        onClick={() => input.current?.click()}
                    >
                        <Upload />
                        {t('image.upload')}
                    </Button>
                    {value !== null && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="bg-destructive/10 text-destructive hover:bg-destructive/15 hover:text-destructive"
                            disabled={disabled}
                            onClick={() => onChange(null)}
                        >
                            {t('image.reset')}
                        </Button>
                    )}
                </div>
                <input
                    ref={input}
                    {...controlProps({ name, error, required, disabled })}
                    type="file"
                    hidden
                    accept={accept
                        .map((extension) => `.${extension}`)
                        .join(',')}
                    onChange={(event) => {
                        onChange(event.target.files?.[0] ?? null);
                        event.target.value = '';
                    }}
                />
            </div>
        </FormField>
    );
}
