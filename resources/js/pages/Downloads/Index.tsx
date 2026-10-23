import { Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, Download, FileSpreadsheet } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { EmptyState } from '../../components/admin/EmptyState';
import { PageHeader } from '../../components/admin/PageHeader';
import { StatusBadge } from '../../components/admin/StatusBadge';
import type { StatusTone } from '../../components/admin/StatusBadge';
import { Button } from '../../components/ui/button';
import { formatDateTime, formatNumber } from '../../lib/format';
import type { MessageKey } from '../../lib/i18n';
import { useT } from '../../lib/i18n';

type Item = Yuan.AdminCore.Data.DownloadItemData;
type Status = Yuan.AdminCore.Exports.ExportStatus;

const STATUS: Record<Status, { label: MessageKey; tone: StatusTone }> = {
    processing: { label: 'downloads.processing', tone: 'info' },
    ready: { label: 'downloads.ready', tone: 'success' },
    failed: { label: 'downloads.failed', tone: 'destructive' },
    expired: { label: 'downloads.expired', tone: 'neutral' },
};

/**
 * The user's queued downloads (frontend-behavior §8.4; derived design: the
 * DataTable rows in a card). While one is processing, the list reloads every
 * 5 seconds and stops when none remain.
 */
export default function Index({
    downloads_list,
}: {
    downloads_list: Yuan.AdminCore.Data.DownloadListData;
}) {
    const t = useT();
    const { app } = usePage().props;
    const items = downloads_list.items;
    const processing = items.some((item) => item.status === 'processing');

    useEffect(() => {
        if (!processing) {
            return;
        }

        const timer = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                router.reload({ only: ['downloads_list', 'downloads'] });
            }
        }, 5_000);

        return () => window.clearInterval(timer);
    }, [processing]);

    const download = (item: Item) =>
        item.url_href &&
        fetch(item.url_href, { headers: { Accept: 'application/json' } })
            .then((response) =>
                response.ok
                    ? (response.json() as Promise<Yuan.AdminCore.Data.SignedUrlData>)
                    : Promise.reject(response),
            )
            .then((signed) => window.location.assign(signed.url))
            .catch(() => toast.error(t('attachments.download_failed')));

    return (
        <>
            <Head title={t('downloads.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('downloads.title')}
                    description={t('downloads.description', {
                        hours: downloads_list.ttl_hours,
                    })}
                />
                <section
                    aria-label={t('downloads.list')}
                    className="rounded bg-card text-card-foreground"
                >
                    {items.length === 0 ? (
                        <EmptyState
                            icon={FileSpreadsheet}
                            title={t('downloads.empty_title')}
                            description={t('downloads.empty_text')}
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-3xl border-collapse text-table tabular-nums">
                                <thead>
                                    <tr className="h-10 border-b border-rule text-left text-label text-muted-foreground uppercase">
                                        <th scope="col" className="px-3">
                                            {t('downloads.file')}
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-3 text-right"
                                        >
                                            {t('downloads.rows')}
                                        </th>
                                        <th scope="col" className="px-3">
                                            {t('downloads.status')}
                                        </th>
                                        <th scope="col" className="px-3">
                                            {t('downloads.created')}
                                        </th>
                                        <th scope="col" className="px-3">
                                            {t('downloads.expires')}
                                        </th>
                                        <th scope="col" className="w-12">
                                            <span className="sr-only">
                                                {t('table.actions')}
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="border-b border-border align-top last:border-b-0"
                                        >
                                            <td className="px-3 py-3">
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-medium">
                                                        {item.module_label}
                                                    </span>
                                                    {item.file_name && (
                                                        <span className="text-xs text-muted-foreground">
                                                            {item.file_name}
                                                        </span>
                                                    )}
                                                    {item.error && (
                                                        <span
                                                            role="alert"
                                                            className="mt-1 flex items-start gap-1.5 text-xs text-destructive"
                                                        >
                                                            <AlertCircle
                                                                aria-hidden="true"
                                                                className="mt-px size-3.5 shrink-0"
                                                            />
                                                            {item.error}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-right">
                                                {formatNumber(
                                                    item.row_count,
                                                    0,
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge
                                                    label={t(
                                                        STATUS[item.status]
                                                            .label,
                                                    )}
                                                    tone={
                                                        STATUS[item.status].tone
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                {formatDateTime(
                                                    item.created_at,
                                                    app.timezone,
                                                )}
                                            </td>
                                            <td className="px-3 py-3 whitespace-nowrap text-muted-foreground">
                                                {item.expires_at
                                                    ? formatDateTime(
                                                          item.expires_at,
                                                          app.timezone,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-2 py-2 text-right">
                                                {item.url_href && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        aria-label={t(
                                                            'downloads.download',
                                                            {
                                                                name:
                                                                    item.file_name ??
                                                                    item.module_label,
                                                            },
                                                        )}
                                                        onClick={() =>
                                                            void download(item)
                                                        }
                                                    >
                                                        <Download />
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
