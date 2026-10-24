import { Deferred, Link, router, usePage } from '@inertiajs/react';
import { RotateCw } from 'lucide-react';
import { formatLocalDate, formatNumber } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { Skeleton } from '../ui/skeleton';
import { BarChartCard } from './BarChartCard';
import { SegmentBar } from './SegmentBar';
import { StatStack } from './StatStack';
import { StatTile } from './StatTile';
import { StatusBadge } from './StatusBadge';
import { SeeAll, WidgetTable } from './WidgetTable';

type Meta = Yuan.AdminCore.Data.Widgets.WidgetMetaData;
type WidgetData =
    | Yuan.AdminCore.Data.Widgets.StatWidgetData
    | Yuan.AdminCore.Data.Widgets.StatStackWidgetData
    | Yuan.AdminCore.Data.Widgets.SegmentWidgetData
    | Yuan.AdminCore.Data.Widgets.BarChartWidgetData
    | Yuan.AdminCore.Data.Widgets.TableWidgetData;

/** 12 columns from 1280px; sm half and md / lg full width from 768px; one column below (frontend-behavior §8.6). */
const SIZES: Record<Meta['size'], string> = {
    sm: 'md:col-span-6 xl:col-span-3',
    md: 'md:col-span-12 xl:col-span-6',
    lg: 'md:col-span-12 xl:col-span-9',
    full: 'md:col-span-12',
};

const DATE = /^\d{4}-\d{2}-\d{2}$/;

/**
 * Beranda's cards (spec §7.2): each waits for its own deferred prop
 * (skeleton), shows "Gagal memuat" with a retry when the server rescued it,
 * and renders by type.
 */
export function WidgetGrid({ widgets }: { widgets: Meta[] }) {
    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-12 lg:gap-6 xl:grid-flow-row-dense">
            {widgets.map((widget) => (
                <div
                    key={widget.key}
                    className={cn(
                        'min-w-0 [&>*]:h-full',
                        SIZES[widget.size],
                        widget.tall && 'xl:row-span-2',
                    )}
                >
                    <Deferred
                        data={widget.prop}
                        fallback={<WidgetCard widget={widget} />}
                        rescue={<Failed widget={widget} />}
                    >
                        <Loaded widget={widget} />
                    </Deferred>
                </div>
            ))}
        </div>
    );
}

function Loaded({ widget }: { widget: Meta }) {
    const data = usePage().props[widget.prop] as WidgetData | undefined;

    return data === undefined ? (
        <Failed widget={widget} />
    ) : (
        <WidgetCard widget={widget} data={data} />
    );
}

function Failed({ widget }: { widget: Meta }) {
    const t = useT();

    return (
        <section
            aria-label={widget.label}
            className="flex min-w-0 flex-col gap-3 rounded bg-card p-6 text-card-foreground"
        >
            <h2 className="border-b-2 border-rule pb-3 text-card-title">
                {widget.label}
            </h2>
            <p className="m-0 text-table text-muted-foreground">
                {t('widgets.failed')}
            </p>
            <div>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => router.reload({ only: [widget.prop] })}
                >
                    <RotateCw />
                    {t('widgets.retry')}
                </Button>
            </div>
        </section>
    );
}

/** Nothing to draw: no figures, or every bar and segment at zero (a stat of 0 is a value). */
function isEmpty(data: WidgetData): boolean {
    const figures =
        'items' in data
            ? data.items
            : 'segments' in data
              ? data.segments
              : 'bars' in data
                ? data.bars
                : null;

    return figures !== null && figures.every((figure) => figure.value === 0);
}

/** One card; without `data` it is the skeleton of its type. */
function WidgetCard({ widget, data }: { widget: Meta; data?: WidgetData }) {
    const t = useT();
    const loading = data === undefined;
    const seeAll = widget.href ? (
        <SeeAll href={widget.href} label={t('widgets.see_all')} />
    ) : undefined;
    const go = (href: string | null) => href && router.visit(href);

    if (!loading && isEmpty(data)) {
        return (
            <section
                aria-label={widget.label}
                className="flex min-w-0 flex-col gap-3 rounded bg-card p-6 text-card-foreground"
            >
                <div className="flex flex-wrap items-baseline gap-x-4 gap-y-2 border-b-2 border-rule pb-3">
                    <h2 className="flex-1 text-card-title">{widget.label}</h2>
                    {seeAll}
                </div>
                <p className="m-0 text-table text-muted-foreground">
                    {widget.empty}
                </p>
            </section>
        );
    }

    switch (widget.type) {
        case 'stat': {
            const stat = data as
                Yuan.AdminCore.Data.Widgets.StatWidgetData | undefined;
            const tile = (
                <StatTile
                    icon={stat?.icon ?? 'activity'}
                    label={widget.label}
                    value={stat?.value ?? 0}
                    format={stat?.format}
                    loading={loading}
                    badge={
                        stat?.badge ? (
                            <StatusBadge tone="warning" label={stat.badge} />
                        ) : undefined
                    }
                />
            );

            return widget.href && !loading ? (
                <Link
                    href={widget.href}
                    className="block rounded hover:ring-1 hover:ring-border [&>section]:h-full"
                >
                    {tile}
                </Link>
            ) : (
                tile
            );
        }
        case 'stat_stack': {
            const items = (
                data as
                    Yuan.AdminCore.Data.Widgets.StatStackWidgetData | undefined
            )?.items ?? [
                { key: '-', label: '', value: 0, format: 'number', href: null },
            ];

            return (
                <StatStack label={widget.label} items={items} loading={loading}>
                    {seeAll}
                </StatStack>
            );
        }
        case 'segment': {
            const segments =
                (
                    data as
                        | Yuan.AdminCore.Data.Widgets.SegmentWidgetData
                        | undefined
                )?.segments ??
                [0, 1, 2].map((key) => ({
                    key: String(key),
                    label: '',
                    value: 0,
                    format: 'number' as const,
                    href: null,
                }));

            return (
                <SegmentBar
                    title={widget.label}
                    segments={segments}
                    loading={loading}
                    action={seeAll}
                    onSelect={(key) =>
                        go(
                            segments.find((segment) => segment.key === key)
                                ?.href ?? null,
                        )
                    }
                />
            );
        }
        case 'bar_chart': {
            const chart = data as
                Yuan.AdminCore.Data.Widgets.BarChartWidgetData | undefined;
            const label = (x: string) =>
                DATE.test(x) ? formatLocalDate(x) : x;

            return (
                <BarChartCard
                    title={widget.label}
                    subtitle={seeAll}
                    data={(chart?.bars ?? []).map((bar) => ({
                        x: bar.key,
                        y: bar.value,
                    }))}
                    xFormat={label}
                    valueLabel={chart?.unit ?? ''}
                    tooltip={(x, y) => ({
                        title: `${formatNumber(y)} ${chart?.unit ?? ''}`,
                        detail: label(x),
                    })}
                    onSelect={(x) =>
                        go(
                            chart?.bars.find((bar) => bar.key === x)?.href ??
                                null,
                        )
                    }
                    loading={loading}
                />
            );
        }
        case 'table': {
            const table = data as
                Yuan.AdminCore.Data.Widgets.TableWidgetData | undefined;

            return loading ? (
                <section
                    aria-label={widget.label}
                    aria-busy="true"
                    className="flex min-w-0 flex-col gap-3 rounded bg-card p-6 text-card-foreground"
                >
                    <h2 className="border-b-2 border-rule pb-3 text-card-title">
                        {widget.label}
                    </h2>
                    {[0, 1, 2, 3, 4].map((row) => (
                        <Skeleton key={row} className="h-4 w-full" />
                    ))}
                </section>
            ) : (
                <WidgetTable
                    title={widget.label}
                    columns={table?.columns ?? []}
                    rows={table?.rows ?? []}
                    href={widget.href}
                    empty={widget.empty}
                />
            );
        }
    }
}
