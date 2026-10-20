import { router, usePage } from '@inertiajs/react';
import { Download, FileText, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { formatDateTime, formatFileSize } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { Button } from '../ui/button';
import { ConfirmDialog } from './ConfirmDialog';
import { EmptyState } from './EmptyState';

type File = Yuan.AdminCore.Data.AttachmentData;

/**
 * A record's files (frontend-behavior §8.5): name, size · uploader · date,
 * download (a fresh signed URL is asked for on click, since they expire) and
 * delete when allowed.
 */
export function AttachmentList({ items }: { items: File[] }) {
    const t = useT();
    const { app } = usePage().props;
    const [deleting, setDeleting] = useState<File | null>(null);

    const download = (file: File) =>
        fetch(file.url_href, { headers: { Accept: 'application/json' } })
            .then((response) =>
                response.ok
                    ? (response.json() as Promise<Yuan.AdminCore.Data.SignedUrlData>)
                    : Promise.reject(response),
            )
            .then((signed) => window.location.assign(signed.url))
            .catch(() => toast.error(t('attachments.download_failed')));

    if (items.length === 0) {
        return <EmptyState icon={FileText} title={t('attachments.empty')} />;
    }

    return (
        <>
            <ul className="m-0 flex list-none flex-col p-0">
                {items.map((file) => (
                    <li
                        key={file.id}
                        className="flex items-center gap-3 border-b border-border py-3 text-table last:border-b-0"
                    >
                        <FileText
                            aria-hidden="true"
                            className="size-4 shrink-0 text-muted-foreground"
                        />
                        <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span className="truncate font-medium">
                                {file.name}
                            </span>
                            <span className="text-xs text-muted-foreground tabular-nums">
                                {t('attachments.meta', {
                                    size: formatFileSize(file.size),
                                    user: file.uploaded_by ?? '—',
                                    date: formatDateTime(
                                        file.created_at,
                                        app.timezone,
                                    ),
                                })}
                            </span>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label={t('attachments.download', {
                                name: file.name,
                            })}
                            onClick={() => void download(file)}
                        >
                            <Download />
                        </Button>
                        {file.destroy_href && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8 text-destructive hover:text-destructive"
                                aria-label={t('attachments.delete', {
                                    name: file.name,
                                })}
                                onClick={() => setDeleting(file)}
                            >
                                <Trash2 />
                            </Button>
                        )}
                    </li>
                ))}
            </ul>
            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title={t('attachments.delete_title')}
                itemName={deleting?.name ?? ''}
                description={t('attachments.delete_text')}
                onConfirm={() => {
                    if (deleting?.destroy_href) {
                        router.delete(deleting.destroy_href, {
                            preserveScroll: true,
                            only: ['attachments'],
                        });
                    }

                    setDeleting(null);
                }}
            />
        </>
    );
}
