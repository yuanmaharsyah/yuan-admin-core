import { ActivityDrawer } from '@admin-core/components/admin/ActivityDrawer';
import { DataTable } from '@admin-core/components/admin/DataTable';
import type { DataTableColumn } from '@admin-core/components/admin/DataTable';
import { FilterBar } from '@admin-core/components/admin/FilterBar';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { RangeFilter } from '@admin-core/components/admin/RangeFilter';
import type { RangeValue } from '@admin-core/components/admin/RangeFilter';
import { StatusBadge } from '@admin-core/components/admin/StatusBadge';
import { usePermission } from '@admin-core/hooks/usePermission';
import { formatDateTime } from '@admin-core/lib/format';
import { useT } from '@admin-core/lib/i18n';
import { Deferred, Head, router, usePage } from '@inertiajs/react';
import { Eye, History } from 'lucide-react';
import { download, index } from '@/routes/audit-log';
import { AuditSummary } from '../components/AuditSummary';

type Row = Yuan.AdminCore.Data.Audit.ActivityItemData;

type Props = {
    events: Yuan.AdminCore.Data.PaginatedData<Row>;
    table: Yuan.AdminCore.Data.DataTableStateData;
    filters: Yuan.AdminCore.Modules.AuditLog.Data.AuditFilterOptionsData;
    summary?: Yuan.AdminCore.Modules.AuditLog.Data.AuditSummaryData;
    event: Yuan.AdminCore.Data.Audit.ActivityDetailData | null;
};

const LIST_ID = 'audit-log-list';

/**
 * Log Aktivitas (frontend-behavior §8.3, reference `audit`): one filter state
 * in the URL. Range, user and module reload the summary and the list; the
 * event group, search and paging reload the list only; `event_id` opens the
 * drawer.
 */
export default function Index({
    events,
    table,
    filters,
    summary,
    event,
}: Props) {
    const t = useT();
    const { app } = usePage().props;
    const { can } = usePermission();

    /**
     * A new range, user or module reloads the whole page; the event group
     * (`listOnly`) only the list. The list starts again at page 1.
     */
    const visit = (
        changes: {
            range?: RangeValue;
            user?: string | null;
            module?: string | null;
            event?: string | null;
        },
        listOnly = false,
    ) => {
        const range = changes.range ?? {
            range: filters.range,
            from: filters.from,
            to: filters.to,
        };
        const filter: Record<string, string> = { ...table.filter };

        for (const key of ['user', 'module', 'event'] as const) {
            const value = changes[key];

            if (value === null) {
                delete filter[key];
            } else if (value !== undefined) {
                filter[key] = value;
            }
        }

        router.get(
            index().url,
            {
                ...(range.range === 'custom'
                    ? range
                    : range.range === '30d'
                      ? {}
                      : { range: range.range }),
                ...(Object.keys(filter).length > 0 ? { filter } : {}),
                ...(table.search ? { search: table.search } : {}),
            },
            {
                preserveScroll: true,
                ...(listOnly
                    ? { only: ['events', 'table'], preserveState: true }
                    : {}),
                onSuccess: () =>
                    document.getElementById(LIST_ID)?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    }),
            },
        );
    };

    /** Opens (or closes, with null) the drawer: only the `event` prop reloads. */
    const showEvent = (id: number | null) => {
        const url = new URL(window.location.href);

        if (id === null) {
            url.searchParams.delete('event_id');
        } else {
            url.searchParams.set('event_id', String(id));
        }

        router.visit(url.pathname + url.search, {
            only: ['event'],
            preserveState: true,
            preserveScroll: true,
            replace: id === null,
        });
    };

    const columns: DataTableColumn<Row>[] = [
        {
            key: 'created_at',
            label: t('audit-log.col_time'),
            className: 'w-40 whitespace-nowrap tabular-nums',
            cell: (row) => formatDateTime(row.created_at, app.timezone),
        },
        {
            key: 'causer_name',
            label: t('audit-log.col_user'),
            cell: (row) =>
                row.causer_name !== null ? (
                    <span className="flex flex-col">
                        <span className="font-medium">{row.causer_name}</span>
                        {row.causer_level && (
                            <span className="text-xs text-muted-foreground">
                                {row.causer_level}
                            </span>
                        )}
                    </span>
                ) : row.attempted_username !== null ? (
                    <span className="flex flex-col">
                        <code className="font-mono">
                            {row.attempted_username}
                        </code>
                        <span className="text-xs text-muted-foreground">
                            {t('activity.unknown_user')}
                        </span>
                    </span>
                ) : (
                    <span className="text-muted-foreground">
                        {t('activity.system')}
                    </span>
                ),
        },
        { key: 'module_label', label: t('audit-log.col_module') },
        {
            key: 'event',
            label: t('audit-log.col_event'),
            cell: (row) => (
                <StatusBadge tone={row.tone} label={row.event_label} />
            ),
        },
        {
            key: 'subject_label',
            label: t('audit-log.col_subject'),
            cell: (row) => (
                <span className="flex min-w-0 flex-col gap-0.5">
                    <span>{row.subject_label ?? row.summary ?? '—'}</span>
                    {row.subject_type && (
                        <span className="text-xs text-muted-foreground">
                            {row.subject_type}
                        </span>
                    )}
                    {row.batch_size !== null && row.batch_size > 1 && (
                        <span className="text-xs text-muted-foreground italic">
                            {t('activity.batch', { count: row.batch_size })}
                        </span>
                    )}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title={t('audit-log.title')} />
            <div className="flex flex-col gap-6">
                <PageHeader
                    title={t('audit-log.title')}
                    description={t('audit-log.description')}
                    actions={
                        <FilterBar
                            label={t('audit-log.filters')}
                            value={{
                                user: filters.user,
                                module: filters.module,
                            }}
                            onChange={(key, value) => visit({ [key]: value })}
                            fields={[
                                {
                                    key: 'user',
                                    label: t('audit-log.user'),
                                    allLabel: t('audit-log.all_users'),
                                    options: filters.users,
                                },
                                {
                                    key: 'module',
                                    label: t('audit-log.module'),
                                    allLabel: t('audit-log.all_modules'),
                                    options: filters.modules,
                                },
                            ]}
                        >
                            <RangeFilter
                                value={{
                                    range: filters.range,
                                    from: filters.from,
                                    to: filters.to,
                                }}
                                maxDays={filters.max_days}
                                presets={[
                                    {
                                        key: '7d',
                                        label: t('audit-log.range_7d'),
                                    },
                                    {
                                        key: '30d',
                                        label: t('audit-log.range_30d'),
                                    },
                                    {
                                        key: '90d',
                                        label: t('audit-log.range_90d'),
                                    },
                                ]}
                                onChange={(range) => visit({ range })}
                            />
                        </FilterBar>
                    }
                />
                <Deferred
                    data="summary"
                    fallback={
                        <AuditSummary
                            selectedEvent={null}
                            onSelectEvent={() => undefined}
                            onSelectDay={() => undefined}
                        />
                    }
                >
                    <AuditSummary
                        summary={summary}
                        selectedEvent={table.filter.event ?? null}
                        onSelectEvent={(key) =>
                            visit(
                                {
                                    event:
                                        table.filter.event === key ? null : key,
                                },
                                true,
                            )
                        }
                        onSelectDay={(day) =>
                            visit({
                                range: {
                                    range: 'custom',
                                    from: day,
                                    to:
                                        summary?.bucket === 'week'
                                            ? addDays(day, 6)
                                            : day,
                                },
                            })
                        }
                    />
                </Deferred>
                <section
                    id={LIST_ID}
                    aria-labelledby={`${LIST_ID}-title`}
                    className="flex scroll-mt-6 flex-col gap-4"
                >
                    <h2 id={`${LIST_ID}-title`} className="text-section">
                        {t('audit-log.list_title')}
                    </h2>
                    <DataTable
                        data={events}
                        state={table}
                        columns={columns}
                        slug="audit-log"
                        label={t('audit-log.list')}
                        itemLabel={t('audit-log.item')}
                        rowLabel={(row) => row.subject_label ?? row.event_label}
                        searchPlaceholder={t('audit-log.search')}
                        onRowClick={(row) => showEvent(row.id)}
                        rowActions={(row) => [
                            {
                                label: t('audit-log.detail'),
                                icon: Eye,
                                onSelect: () => showEvent(row.id),
                            },
                        ]}
                        download={
                            can('audit-log.download') ? download() : undefined
                        }
                        only={['events', 'table']}
                        empty={{
                            icon: History,
                            title: t('audit-log.empty_title'),
                            description: t('audit-log.empty_text'),
                        }}
                    />
                </section>
            </div>
            <ActivityDrawer detail={event} onClose={() => showEvent(null)} />
        </>
    );
}

/** `YYYY-MM-DD` plus some days, as a calendar date (no timezone). */
function addDays(day: string, days: number): string {
    const date = new Date(`${day}T00:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);

    return date.toISOString().slice(0, 10);
}
