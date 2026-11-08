import { FileText, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { formatFileSize } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { AttachmentList } from './AttachmentList';
import { FormField } from './FormField';

type Slot = Yuan.AdminCore.Data.AttachmentSlotData;

/** A form's files: slot key => one file, a list, or nothing picked. */
export type AttachmentFiles = Record<string, File | File[] | null>;

type Props = {
    name: string;
    slot: Slot;
    /** The picked files (at most one for a single slot). */
    value: File[];
    onChange: (files: File[]) => void;
    /** The server's message for the slot or one of its files. */
    error?: string;
};

/**
 * One attachment slot in a form (frontend-behavior §8.5, derived from the
 * approved upload design): a drop zone, type and size checked before the
 * form is sent, the picked files with remove, and the slot's current files
 * with download and delete. Files are sent with the form; a new file in a
 * single slot replaces the current one.
 */
export function FileField({ name, slot, value, onChange, error }: Props) {
    const t = useT();
    const input = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const [rejected, setRejected] = useState<
        { name: string; reason: string }[]
    >([]);
    const required = slot.required && slot.items.length === 0;

    const add = (files: FileList | null) => {
        const picked = Array.from(files ?? []);
        const refused: { name: string; reason: string }[] = [];
        const accepted = picked.filter((file) => {
            const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
            const reason = !slot.accept.includes(extension)
                ? t('attachments.wrong_type')
                : file.size > slot.max_kb * 1024
                  ? t('attachments.too_big', {
                        size: formatFileSize(slot.max_kb * 1024),
                    })
                  : null;

            if (reason !== null) {
                refused.push({ name: file.name, reason });
            }

            return reason === null;
        });
        setRejected(refused);

        if (accepted.length > 0) {
            onChange(slot.multiple ? [...value, ...accepted] : [accepted[0]]);
        }
    };

    const onDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setDragging(false);
        add(event.dataTransfer.files);
    };

    return (
        <FormField
            name={name}
            label={slot.label}
            required={required}
            error={error}
        >
            <div className="flex flex-col gap-2">
                {slot.items.length > 0 && <AttachmentList items={slot.items} />}
                <div
                    role="button"
                    tabIndex={0}
                    aria-describedby={`${name}-drop-hint`}
                    onClick={() => input.current?.click()}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            input.current?.click();
                        }
                    }}
                    onDragOver={(event) => {
                        event.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={onDrop}
                    className={cn(
                        'flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed px-6 py-10 text-center transition-colors duration-150 hover:bg-muted/50',
                        dragging ? 'border-primary bg-muted' : 'border-border',
                        error && 'border-destructive',
                    )}
                >
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-12 items-center justify-center rounded-full bg-muted transition-transform duration-200',
                            dragging && 'scale-110',
                        )}
                    >
                        <Upload className="size-5" />
                    </span>
                    <span className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t(
                                !slot.multiple && slot.items.length > 0
                                    ? 'attachments.drop_replace'
                                    : 'attachments.drop',
                            )}{' '}
                            <span className="underline underline-offset-3">
                                {t('attachments.browse')}
                            </span>
                        </span>
                        <span
                            id={`${name}-drop-hint`}
                            className="text-table text-muted-foreground"
                        >
                            {t('attachments.rules', {
                                types: slot.accept
                                    .map((extension) => extension.toUpperCase())
                                    .join(', '),
                                size: formatFileSize(slot.max_kb * 1024),
                            })}
                        </span>
                    </span>
                    <input
                        ref={input}
                        id={name}
                        type="file"
                        multiple={slot.multiple}
                        hidden
                        accept={slot.accept
                            .map((extension) => `.${extension}`)
                            .join(',')}
                        onChange={(event) => {
                            add(event.target.files);
                            event.target.value = '';
                        }}
                    />
                </div>
                {(value.length > 0 || rejected.length > 0) && (
                    <ul
                        className="m-0 flex list-none flex-col gap-2 p-0"
                        aria-live="polite"
                    >
                        {value.length > 0 && (
                            <li>
                                <ul className="m-0 grid list-none grid-cols-2 gap-3 p-0 sm:grid-cols-3 lg:grid-cols-4">
                                    {value.map((file, index) => (
                                        <PickedFile
                                            key={`${file.name}-${index}`}
                                            file={file}
                                            removeLabel={t(
                                                'attachments.remove',
                                                {
                                                    name: file.name,
                                                },
                                            )}
                                            onRemove={() =>
                                                onChange(
                                                    value.filter(
                                                        (_, at) => at !== index,
                                                    ),
                                                )
                                            }
                                        />
                                    ))}
                                </ul>
                            </li>
                        )}
                        {rejected.map((file) => (
                            <li
                                key={file.name}
                                className="flex items-center gap-3 rounded border border-destructive px-3 py-2 text-table"
                            >
                                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span className="truncate font-medium">
                                        {file.name}
                                    </span>
                                    <span
                                        role="alert"
                                        className="text-xs text-destructive"
                                    >
                                        {file.reason}
                                    </span>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    aria-label={t('attachments.dismiss', {
                                        name: file.name,
                                    })}
                                    onClick={() =>
                                        setRejected(
                                            rejected.filter(
                                                (other) => other !== file,
                                            ),
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </FormField>
    );
}

/**
 * Every attachment slot of a record as FileFields, bound to the form's
 * `files` (`files.{slot}` errors from the input Data).
 */
export function AttachmentFields({
    slots,
    value,
    onChange,
    errors,
}: {
    slots: Slot[];
    value: AttachmentFiles;
    onChange: (files: AttachmentFiles) => void;
    errors: Partial<Record<string, string>>;
}) {
    return slots.map((slot) => {
        const current = value[slot.key];
        const files =
            current === null || current === undefined
                ? []
                : Array.isArray(current)
                  ? current
                  : [current];
        const error = Object.entries(errors).find(
            ([key]) =>
                key === `files.${slot.key}` ||
                key.startsWith(`files.${slot.key}.`),
        )?.[1];

        return (
            <FileField
                key={slot.key}
                name={`files.${slot.key}`}
                slot={slot}
                value={files}
                error={error}
                onChange={(picked) =>
                    onChange({
                        ...value,
                        [slot.key]: slot.multiple
                            ? picked
                            : (picked[0] ?? null),
                    })
                }
            />
        );
    });
}

/**
 * A picked file before upload: an image shows its own thumbnail (a local
 * object URL, revoked when it goes), anything else its type icon.
 */
function PickedFile({
    file,
    removeLabel,
    onRemove,
}: {
    file: File;
    removeLabel: string;
    onRemove: () => void;
}) {
    const preview = useMemo(
        () =>
            file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
        [file],
    );

    useEffect(
        () => () => {
            if (preview !== null) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    return (
        <li className="group relative flex min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-card">
            <div className="flex aspect-[4/3] items-center justify-center bg-muted">
                {preview ? (
                    <img
                        src={preview}
                        alt=""
                        className="size-full object-cover"
                    />
                ) : (
                    <FileText
                        aria-hidden="true"
                        className="size-8 text-muted-foreground"
                    />
                )}
            </div>
            <div className="flex flex-col px-3 py-2 text-table">
                <span className="truncate font-medium">{file.name}</span>
                <span className="text-xs text-muted-foreground tabular-nums">
                    {formatFileSize(file.size)}
                </span>
            </div>
            <Button
                type="button"
                variant="secondary"
                size="icon"
                className="absolute top-2 right-2 size-8 rounded-full"
                aria-label={removeLabel}
                onClick={onRemove}
            >
                <X />
            </Button>
        </li>
    );
}
