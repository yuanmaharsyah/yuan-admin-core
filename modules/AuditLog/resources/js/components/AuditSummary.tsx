import { BarChartCard } from '@admin-core/components/admin/BarChartCard';
import { SegmentBar } from '@admin-core/components/admin/SegmentBar';
import { StatStack } from '@admin-core/components/admin/StatStack';
import { StatTile } from '@admin-core/components/admin/StatTile';
import { StatusBadge } from '@admin-core/components/admin/StatusBadge';
import { formatLocalDate, formatNumber } from '@admin-core/lib/format';
import { useT } from '@admin-core/lib/i18n';
import { List, ShieldAlert, Users } from 'lucide-react';

type Summary = Yuan.AdminCore.Modules.AuditLog.Data.AuditSummaryData;

type Props = {
    /** Undefined while the deferred prop loads: every card shows its skeleton. */
    summary?: Summary;
    selectedEvent: string | null;
    onSelectEvent: (key: string) => void;
    onSelectDay: (day: string) => void;
};

const EMPTY: Summary = {
    total: 0,
    active_users: 0,
    failed_logins: 0,
    downloads: 0,
    rows_downloaded: 0,
    permission_changes: 0,
    logins: 0,
    by_type: [],
    by_day: [],
    bucket: 'day',
    from: '',
    to: '',
};

/** The Log Aktivitas summary (reference `audit`): 3 tiles, the stat stack, per type, per day. */
export function AuditSummary({
    summary,
    selectedEvent,
    onSelectEvent,
    onSelectDay,
}: Props) {
    const t = useT();
    const loading = summary === undefined;
    const data = summary ?? EMPTY;
    const weekly = data.bucket === 'week';
    const loginMax = Math.max(data.logins, data.failed_logins, 1);

    return (
        <div className="grid gap-4 lg:grid-cols-12 lg:gap-6">
            <div className="lg:col-span-3">
                <StatTile
                    icon={List}
                    label={t('audit-log.total')}
                    value={data.total}
                    loading={loading}
                />
            </div>
            <div className="lg:col-span-3">
                <StatTile
                    icon={Users}
                    label={t('audit-log.active_users')}
                    value={data.active_users}
                    loading={loading}
                />
            </div>
            <div className="lg:col-span-3">
                <StatTile
                    icon={ShieldAlert}
                    label={t('audit-log.failed_logins')}
                    value={data.failed_logins}
                    loading={loading}
                    badge={
                        data.failed_logins > 0 ? (
                            <StatusBadge
                                tone="warning"
                                label={t('audit-log.check')}
                            />
                        ) : undefined
                    }
                />
            </div>
            <div className="lg:col-span-3 lg:col-start-10 lg:row-span-2 lg:row-start-1 [&>section]:h-full">
                <StatStack
                    label={t('audit-log.stack')}
                    loading={loading}
                    items={[
                        {
                            label: t('audit-log.downloads'),
                            value: data.downloads,
                        },
                        {
                            label: t('audit-log.rows_downloaded'),
                            value: data.rows_downloaded,
                        },
                        {
                            label: t('audit-log.permission_changes'),
                            value: data.permission_changes,
                        },
                    ]}
                >
                    {!loading && (
                        <div className="flex flex-col gap-2.5">
                            <div className="text-label text-muted-foreground uppercase">
                                {t('audit-log.logins')}
                            </div>
                            <div className="flex gap-4 text-xs text-muted-foreground">
                                <span className="flex items-center gap-1.5">
                                    <span
                                        aria-hidden="true"
                                        className="size-2.5 bg-chart-2"
                                    />
                                    {t('audit-log.logins_ok')}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <span
                                        aria-hidden="true"
                                        className="size-2.5 bg-chart-3"
                                    />
                                    {t('audit-log.logins_failed')}
                                </span>
                            </div>
                            <div
                                role="img"
                                aria-label={t('audit-log.logins_bars', {
                                    ok: formatNumber(data.logins),
                                    failed: formatNumber(data.failed_logins),
                                })}
                                className="flex flex-col gap-2"
                            >
                                {[
                                    {
                                        value: data.logins,
                                        fill: 'fill-chart-2',
                                    },
                                    {
                                        value: data.failed_logins,
                                        fill: 'fill-chart-3',
                                    },
                                ].map((bar) => (
                                    <div
                                        key={bar.fill}
                                        className="flex items-center gap-2.5"
                                    >
                                        <svg
                                            aria-hidden="true"
                                            className="h-5 min-w-0 flex-1"
                                            viewBox="0 0 100 20"
                                            preserveAspectRatio="none"
                                        >
                                            <rect
                                                width={
                                                    bar.value > 0
                                                        ? Math.max(
                                                              (bar.value /
                                                                  loginMax) *
                                                                  100,
                                                              2,
                                                          )
                                                        : 2
                                                }
                                                height="20"
                                                rx="2"
                                                className={
                                                    bar.value > 0
                                                        ? bar.fill
                                                        : 'fill-muted'
                                                }
                                            />
                                        </svg>
                                        <span className="min-w-11 text-right text-table font-semibold tabular-nums">
                                            {formatNumber(bar.value)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </StatStack>
            </div>
            <div className="lg:col-span-9 lg:row-start-2">
                <SegmentBar
                    title={t('audit-log.by_type')}
                    segments={
                        loading
                            ? [0, 1, 2, 3, 4].map((key) => ({
                                  key: String(key),
                                  label: '',
                                  value: 0,
                              }))
                            : data.by_type
                    }
                    selected={selectedEvent}
                    onSelect={onSelectEvent}
                    loading={loading}
                    selectLabel={(segment) =>
                        t('audit-log.by_type_filter', {
                            label: segment.label,
                            value: formatNumber(segment.value),
                        })
                    }
                />
            </div>
            <div className="lg:col-span-12">
                <BarChartCard
                    title={
                        weekly ? t('audit-log.by_week') : t('audit-log.by_day')
                    }
                    subtitle={
                        loading
                            ? undefined
                            : `${formatLocalDate(data.from)} – ${formatLocalDate(data.to)}`
                    }
                    data={data.by_day.map((bucket) => ({
                        x: bucket.key,
                        y: bucket.value,
                    }))}
                    xFormat={(day) => formatLocalDate(day)}
                    valueLabel={t('audit-log.count')}
                    tooltip={(day, count) => ({
                        title: t('audit-log.tip_count', {
                            count: formatNumber(count),
                        }),
                        detail: weekly
                            ? t('audit-log.tip_week', {
                                  date: formatLocalDate(day),
                              })
                            : formatLocalDate(day),
                    })}
                    onSelect={onSelectDay}
                    loading={loading}
                />
            </div>
        </div>
    );
}
