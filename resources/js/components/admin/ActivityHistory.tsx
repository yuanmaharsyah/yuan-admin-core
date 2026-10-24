import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, History } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { formatDateTime } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { Skeleton } from '../ui/skeleton';
import { DiffTable } from './DiffTable';
import { EmptyState } from './EmptyState';
import { StatusBadge } from './StatusBadge';

type History = Yuan.AdminCore.Data.Audit.ActivityHistoryData;
type Item = Yuan.AdminCore.Data.Audit.ActivityItemData;

type Props = {
    /** The optional prop (`ActivityHistory::prop()`); undefined until the tab asks for it. */
    history?: History | null;
    /** The prop's name on the page. */
    prop?: string;
};

/**
 * A record's Riwayat tab (frontend-behavior §8.3a): loads its optional prop
 * the first time it renders, 20 events at a time; a row opens its changed
 * fields inline, and a batch links to the Log Aktivitas.
 */
export function ActivityHistory({ history, prop = 'history' }: Props) {
    const t = useT();
    const { app } = usePage().props;
    const [items, setItems] = useState<Item[]>(history?.items ?? []);
    const [seen, setSeen] = useState(history);
    const [append, setAppend] = useState(false);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const [open, setOpen] = useState<number[]>([]);

    // A page of the prop arrived: "Muat lebih banyak" adds it, anything else replaces.
    if (history && history !== seen) {
        setSeen(history);
        setItems(append ? [...items, ...history.items] : history.items);
        setAppend(false);
    }

    const load = useCallback(
        (page: number) =>
            router.reload({
                only: [prop],
                data: page > 1 ? { history_page: page } : {},
                preserveUrl: true,
                onStart: () => {
                    setAppend(page > 1);
                    setLoading(true);
                    setFailed(false);
                },
                onFinish: () => setLoading(false),
                onHttpException: () => {
                    setFailed(true);

                    return false;
                },
                onNetworkError: () => {
                    setFailed(true);

                    return false;
                },
            }),
        [prop],
    );

    // The tab's first render asks for the optional prop.
    useEffect(() => {
        if (history === undefined) {
            load(1);
        }
    }, [history, load]);

    if (failed && items.length === 0) {
        return (
            <div
                role="alert"
                className="flex flex-wrap items-center gap-3 rounded border border-destructive/40 p-4 text-table"
            >
                <div className="flex flex-1 basis-52 flex-col gap-0.5">
                    <strong className="font-semibold text-destructive">
                        {t('history.failed')}
                    </strong>
                    <span>{t('history.failed_text')}</span>
                </div>
                <Button variant="outline" size="sm" onClick={() => load(1)}>
                    {t('table.retry')}
                </Button>
            </div>
        );
    }

    if (history === undefined || (loading && items.length === 0)) {
        return (
            <ul
                aria-busy="true"
                aria-label={t('history.label')}
                className="flex flex-col"
            >
                {[0, 1, 2, 3].map((row) => (
                    <li
                        key={row}
                        className="flex flex-col gap-2 border-b border-border py-3"
                    >
                        <Skeleton className="h-5.5 w-20" />
                        <Skeleton className="h-3 w-2/3" />
                    </li>
                ))}
            </ul>
        );
    }

    if (items.length === 0) {
        return <EmptyState icon={History} title={t('history.empty')} />;
    }

    return (
        <div className="flex flex-col gap-4">
            <ul aria-label={t('history.label')} className="flex flex-col">
                {items.map((item) => {
                    const expanded = open.includes(item.id);
                    const canExpand = item.diff.length > 0;

                    return (
                        <li
                            key={item.id}
                            className="border-b border-border py-3 last:border-b-0"
                        >
                            <button
                                type="button"
                                disabled={!canExpand}
                                aria-expanded={canExpand ? expanded : undefined}
                                onClick={() =>
                                    setOpen(
                                        expanded
                                            ? open.filter(
                                                  (id) => id !== item.id,
                                              )
                                            : [...open, item.id],
                                    )
                                }
                                className="flex w-full items-start gap-3 rounded text-left text-table disabled:cursor-default"
                            >
                                <span className="flex min-w-0 flex-1 flex-col gap-1.5">
                                    <span className="flex flex-wrap items-center gap-2">
                                        <StatusBadge
                                            tone={item.tone}
                                            label={item.event_label}
                                        />
                                        <span className="text-muted-foreground tabular-nums">
                                            {formatDateTime(
                                                item.created_at,
                                                app.timezone,
                                            )}
                                        </span>
                                    </span>
                                    <span className="flex flex-wrap items-center gap-x-1.5">
                                        <span className="font-medium">
                                            {item.causer_name ??
                                                item.attempted_username ??
                                                t('activity.system')}
                                        </span>
                                        {item.causer_level && (
                                            <span className="text-muted-foreground">
                                                {item.causer_level}
                                            </span>
                                        )}
                                        {item.summary && (
                                            <span className="text-muted-foreground">
                                                · {item.summary}
                                            </span>
                                        )}
                                    </span>
                                </span>
                                {canExpand && (
                                    <ChevronDown
                                        aria-hidden="true"
                                        className={cn(
                                            'mt-1 size-4 shrink-0 text-muted-foreground transition-transform',
                                            expanded && 'rotate-180',
                                        )}
                                    />
                                )}
                            </button>
                            {item.batch_href && item.batch_size !== null && (
                                <Link
                                    href={item.batch_href}
                                    className="mt-1 inline-block text-xs text-foreground italic underline-offset-4 hover:underline"
                                >
                                    {t('activity.batch', {
                                        count: item.batch_size,
                                    })}{' '}
                                    ›
                                </Link>
                            )}
                            {expanded && (
                                <div className="mt-2 max-w-140">
                                    <DiffTable rows={item.diff} />
                                </div>
                            )}
                        </li>
                    );
                })}
            </ul>
            {history?.next_page && (
                <Button
                    variant="outline"
                    className="self-start bg-transparent"
                    loading={loading}
                    onClick={() => history.next_page && load(history.next_page)}
                >
                    {t('history.more')}
                </Button>
            )}
        </div>
    );
}
