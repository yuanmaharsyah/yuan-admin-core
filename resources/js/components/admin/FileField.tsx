import { FileText, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
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
            help={t('attachments.rules', {
                types: slot.accept
                    .map((extension) => extension.toUpperCase())
                    .join(', '),
                size: formatFileSize(slot.max_kb * 1024),
            })}
            error={error}
        >
            <div className="flex flex-col gap-2">
                {slot.items.length > 0 && <AttachmentList items={slot.items} />}
                <div
                    onDragOver={(event) => {
                        event.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={onDrop}
                    className={cn(
                        'flex flex-col items-center gap-2 rounded border border-dashed px-6 py-5 text-center text-table',
                        dragging ? 'border-primary bg-muted' : 'border-border',
                        error && 'border-destructive',
                    )}
                >
                    <Upload
                        aria-hidden="true"
                        className="size-5 text-muted-foreground"
                    />
                    <p className="m-0">
                        {t(
                            !slot.multiple && slot.items.length > 0
                                ? 'attachments.drop_replace'
                                : 'attachments.drop',
                        )}{' '}
                        <Button
                            type="button"
                            variant="link"
                            className="h-auto p-0 text-table font-semibold underline underline-offset-3"
                            onClick={() => input.current?.click()}
                        >
                            {t('attachments.browse')}
                        </Button>
                    </p>
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
                        {value.map((file, index) => (
                            <li
                                key={`${file.name}-${index}`}
                                className="flex items-center gap-3 rounded border border-border px-3 py-2 text-table"
                            >
                                <FileText
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-muted-foreground"
                                />
                                <span className="min-w-0 flex-1 truncate font-medium">
                                    {file.name}
                                </span>
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {formatFileSize(file.size)}
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    aria-label={t('attachments.remove', {
                                        name: file.name,
                                    })}
                                    onClick={() =>
                                        onChange(
                                            value.filter(
                                                (_, at) => at !== index,
                                            ),
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            </li>
                        ))}
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
