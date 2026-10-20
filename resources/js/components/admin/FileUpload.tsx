import { router } from '@inertiajs/react';
import { FileText, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { formatFileSize } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';

type Props = {
    /** The module's `attachments.store` URL for this record. */
    uploadHref: string;
    /** Allowed extensions, without the dot. */
    accept: string[];
    maxKb: number;
    /** Partial reload after each upload; defaults to the `attachments` prop. */
    reloadOnly?: string[];
};

type Item = {
    id: number;
    file: File;
    progress: number;
    status: 'queued' | 'uploading' | 'failed';
    error?: string;
};

const PARALLEL = 3;

/**
 * Drop zone and per-file progress (frontend-behavior §8.5, derived design):
 * type and size are checked before upload, at most 3 files upload at once,
 * each can be cancelled, and the server's error is shown per file. Finished
 * files leave this list and appear in the AttachmentList.
 */
export function FileUpload({
    uploadHref,
    accept,
    maxKb,
    reloadOnly = ['attachments'],
}: Props) {
    const t = useT();
    const input = useRef<HTMLInputElement>(null);
    const requests = useRef(new Map<number, XMLHttpRequest>());
    const nextId = useRef(0);
    const [items, setItems] = useState<Item[]>([]);
    const [dragging, setDragging] = useState(false);
    // The list lives in a ref too, so starting uploads never happens inside a
    // state updater (React may run those twice).
    const list = useRef<Item[]>([]);

    const commit = (next: Item[]) => {
        list.current = next;
        setItems(next);
    };

    const update = (id: number, changes: Partial<Item> | null) =>
        commit(
            changes === null
                ? list.current.filter((item) => item.id !== id)
                : list.current.map((item) =>
                      item.id === id ? { ...item, ...changes } : item,
                  ),
        );

    /** Starts queued files while fewer than PARALLEL are running. */
    const pump = () => {
        let free =
            PARALLEL -
            list.current.filter((item) => item.status === 'uploading').length;
        const started: Item[] = [];

        commit(
            list.current.map((item) => {
                if (item.status !== 'queued' || free <= 0) {
                    return item;
                }

                free--;
                started.push(item);

                return { ...item, status: 'uploading' };
            }),
        );
        started.forEach(send);
    };

    const send = (item: Item) => {
        const xhr = new XMLHttpRequest();
        requests.current.set(item.id, xhr);
        xhr.open('POST', uploadHref);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        xhr.upload.onprogress = (event) =>
            event.lengthComputable &&
            update(item.id, {
                progress: Math.round((event.loaded / event.total) * 100),
            });
        xhr.onloadend = () => {
            requests.current.delete(item.id);

            if (xhr.status === 0) {
                // A cancel already removed the item; otherwise the network dropped.
                if (list.current.some((current) => current.id === item.id)) {
                    update(item.id, {
                        status: 'failed',
                        error: t('attachments.failed'),
                    });
                }
            } else if (xhr.status >= 200 && xhr.status < 300) {
                update(item.id, null);
                router.reload({ only: reloadOnly });
            } else {
                update(item.id, {
                    status: 'failed',
                    error: serverError(xhr) ?? t('attachments.failed'),
                });
            }

            pump();
        };
        const body = new FormData();
        body.append('file', item.file);
        xhr.send(body);
    };

    const add = (files: FileList | null) => {
        const added = Array.from(files ?? []).map((file): Item => {
            const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
            const error = !accept.includes(extension)
                ? t('attachments.wrong_type')
                : file.size > maxKb * 1024
                  ? t('attachments.too_big', {
                        size: formatFileSize(maxKb * 1024),
                    })
                  : undefined;

            return {
                id: nextId.current++,
                file,
                progress: 0,
                status: error ? 'failed' : 'queued',
                error,
            };
        });
        commit([...list.current, ...added]);
        pump();
    };

    const cancel = (item: Item) => {
        const xhr = requests.current.get(item.id);
        update(item.id, null);
        xhr?.abort();
    };

    const onDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setDragging(false);
        add(event.dataTransfer.files);
    };

    return (
        <div className="flex flex-col gap-3">
            <div
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                className={cn(
                    'flex flex-col items-center gap-2 rounded border border-dashed px-6 py-8 text-center text-table',
                    dragging ? 'border-primary bg-muted' : 'border-border',
                )}
            >
                <Upload
                    aria-hidden="true"
                    className="size-5 text-muted-foreground"
                />
                <p className="m-0">
                    {t('attachments.drop')}{' '}
                    <Button
                        type="button"
                        variant="link"
                        className="h-auto p-0 text-table font-semibold underline underline-offset-3"
                        onClick={() => input.current?.click()}
                    >
                        {t('attachments.browse')}
                    </Button>
                </p>
                <p className="m-0 text-xs text-muted-foreground">
                    {t('attachments.rules', {
                        types: accept
                            .map((extension) => extension.toUpperCase())
                            .join(', '),
                        size: formatFileSize(maxKb * 1024),
                    })}
                </p>
                <input
                    ref={input}
                    type="file"
                    multiple
                    hidden
                    accept={accept
                        .map((extension) => `.${extension}`)
                        .join(',')}
                    onChange={(event) => {
                        add(event.target.files);
                        event.target.value = '';
                    }}
                />
            </div>
            {items.length > 0 && (
                <ul
                    className="m-0 flex list-none flex-col gap-2 p-0"
                    aria-live="polite"
                >
                    {items.map((item) => (
                        <li
                            key={item.id}
                            className="flex items-center gap-3 rounded border border-border px-3 py-2 text-table"
                        >
                            <FileText
                                aria-hidden="true"
                                className="size-4 shrink-0 text-muted-foreground"
                            />
                            <div className="flex min-w-0 flex-1 flex-col gap-1">
                                <span className="truncate font-medium">
                                    {item.file.name}
                                </span>
                                {item.status === 'failed' ? (
                                    <span
                                        role="alert"
                                        className="text-xs text-destructive"
                                    >
                                        {item.error}
                                    </span>
                                ) : (
                                    <progress
                                        value={item.progress}
                                        max={100}
                                        aria-label={t('attachments.uploading', {
                                            percent: item.progress,
                                        })}
                                        className="h-1 w-full appearance-none overflow-hidden rounded bg-muted [&::-moz-progress-bar]:bg-primary [&::-webkit-progress-bar]:bg-muted [&::-webkit-progress-value]:bg-primary"
                                    />
                                )}
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                aria-label={
                                    item.status === 'failed'
                                        ? t('attachments.dismiss', {
                                              name: item.file.name,
                                          })
                                        : t('attachments.cancel', {
                                              name: item.file.name,
                                          })
                                }
                                onClick={() => cancel(item)}
                            >
                                <X />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((part) => part.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

/** The first validation message of a 422, or the server's message. */
function serverError(xhr: XMLHttpRequest): string | undefined {
    try {
        const body = JSON.parse(xhr.responseText) as {
            message?: string;
            errors?: Record<string, string[]>;
        };

        return body.errors?.file?.[0] ?? body.message;
    } catch {
        return undefined;
    }
}
