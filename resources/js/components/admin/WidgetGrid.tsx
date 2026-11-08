import { Deferred, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, LayoutGrid, RotateCw } from 'lucide-react';
import {
    formatCurrency,
    formatLocalDate,
    formatNumber,
} from '../../lib/format';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { Skeleton } from '../ui/skeleton';
import { AreaChartCard } from './AreaChartCard';
import { BarChartCard } from './BarChartCard';
import { ChangePill } from './ChangePill';
import { DonutCard } from './DonutCard';
import { ListCard } from './ListCard';
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
    | Yuan.AdminCore.Data.Widgets.TableWidgetData
    | Yuan.AdminCore.Data.Widgets.AreaChartWidgetData
    | Yuan.AdminCore.Data.Widgets.DonutWidgetData
    | Yuan.AdminCore.Data.Widgets.ListWidgetData;
type Stat = Yuan.AdminCore.Data.Widgets.StatWidgetData;

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
            {widgets
                .filter((widget) => !widget.highlight)
                .map((widget) => (
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
            className="flex min-w-0 flex-col gap-3 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <h2 className="text-card-title">{widget.label}</h2>
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
                ? [...data.bars, ...data.negative]
                : 'points' in data
                  ? data.points
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
    const seeAllIcon = widget.href ? (
        <SeeAll href={widget.href} label={t('widgets.see_all')} compact />
    ) : undefined;
    const go = (href: string | null) => href && router.visit(href);

    if (!loading && isEmpty(data)) {
        return (
            <section
                aria-label={widget.label}
                className="flex min-w-0 flex-col gap-3 rounded-xl border border-border bg-card p-6 text-card-foreground"
            >
                <div className="flex flex-wrap items-baseline gap-x-4 gap-y-2">
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
            return (
                <StatTile
                    icon={stat?.icon ?? 'activity'}
                    label={widget.label}
                    value={stat?.value ?? 0}
                    format={stat?.format}
                    loading={loading}
                    change={stat?.change}
                    caption={stat?.caption}
                    action={seeAll}
                    badge={
                        stat?.badge ? (
                            <StatusBadge tone="warning" label={stat.badge} />
                        ) : undefined
                    }
                />
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
                DATE.test(x) ? formatLocalDate(x) : (names.get(x) ?? x);

            const below = new Map(
                (chart?.negative ?? []).map((bar) => [bar.key, bar.value]),
            );
            const beside = new Map(
                (chart?.compare ?? []).map((bar) => [bar.key, bar.value]),
            );
            const summary = chart?.summary ?? [];
            const names = new Map(
                (chart?.bars ?? []).map((bar) => [bar.key, bar.label]),
            );

            return (
                <BarChartCard
                    title={widget.label}
                    subtitle={summary.length > 0 ? undefined : seeAll}
                    aside={
                        summary.length > 0 ? (
                            <ChartSummary
                                figures={summary}
                                href={widget.href}
                            />
                        ) : undefined
                    }
                    data={(chart?.bars ?? []).map((bar) => ({
                        x: bar.key,
                        y: bar.value,
                        n: below.get(bar.key),
                        c: beside.get(bar.key),
                    }))}
                    legend={
                        chart?.series && chart.compare_series
                            ? [chart.series, chart.compare_series]
                            : undefined
                    }
                    xFormat={label}
                    tickFormat={(x) =>
                        DATE.test(x)
                            ? `${x.slice(8, 10)}/${x.slice(5, 7)}`
                            : label(x)
                    }
                    valueLabel={chart?.unit ?? ''}
                    tooltip={(x, y, n) => ({
                        title:
                            n === undefined
                                ? `${formatNumber(y)} ${chart?.unit ?? ''}`
                                : `${formatNumber(y)} / −${formatNumber(n)} ${chart?.unit ?? ''}`,
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
        case 'area_chart': {
            const area = data as
                Yuan.AdminCore.Data.Widgets.AreaChartWidgetData | undefined;

            return (
                <AreaChartCard
                    title={widget.label}
                    value={area?.value ?? 0}
                    format={area?.format}
                    change={area?.change}
                    caption={area?.caption}
                    data={(area?.points ?? []).map((point) => ({
                        x: point.key,
                        y: point.value,
                    }))}
                    xFormat={(x) => (DATE.test(x) ? formatLocalDate(x) : x)}
                    action={seeAllIcon}
                    loading={loading}
                />
            );
        }
        case 'donut': {
            const donut = data as
                Yuan.AdminCore.Data.Widgets.DonutWidgetData | undefined;
            const segments = donut?.segments ?? [];

            return (
                <DonutCard
                    title={widget.label}
                    value={donut?.value ?? 0}
                    format={donut?.format}
                    change={donut?.change}
                    caption={donut?.caption}
                    segments={segments}
                    onSelect={(key) =>
                        go(
                            segments.find((segment) => segment.key === key)
                                ?.href ?? null,
                        )
                    }
                    action={seeAllIcon}
                    loading={loading}
                />
            );
        }
        case 'list': {
            const list = data as
                Yuan.AdminCore.Data.Widgets.ListWidgetData | undefined;

            return (
                <ListCard
                    title={widget.label}
                    rows={list?.rows ?? []}
                    empty={widget.empty}
                    action={widget.size === 'sm' ? seeAllIcon : seeAll}
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
                    className="flex min-w-0 flex-col gap-3 rounded-xl border border-border bg-card p-6 text-card-foreground"
                >
                    <h2 className="text-card-title">{widget.label}</h2>
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

/** The bar chart's side panel: the first figure large, the others with a dot, and the full-report button. */
function ChartSummary({
    figures,
    href,
}: {
    figures: Yuan.AdminCore.Data.Widgets.WidgetFigureData[];
    href: string | null;
}) {
    const t = useT();
    const show = (figure: Yuan.AdminCore.Data.Widgets.WidgetFigureData) =>
        figure.format === 'currency'
            ? formatCurrency(figure.value)
            : formatNumber(figure.value);
    const [first, ...rest] = figures;

    return (
        <>
            {first && (
                <div className="flex items-center gap-3">
                    <span
                        aria-hidden="true"
                        className="flex size-11 shrink-0 items-center justify-center rounded-lg border border-border"
                    >
                        <LayoutGrid className="size-5" />
                    </span>
                    <span className="flex flex-col">
                        <span className="text-section tabular-nums">
                            {show(first)}
                        </span>
                        <span className="text-body text-muted-foreground">
                            {first.label}
                        </span>
                    </span>
                </div>
            )}
            {rest.map((figure, index) => (
                <div key={figure.key} className="flex gap-3 pl-1">
                    <span
                        aria-hidden="true"
                        className={cn(
                            'mt-2 size-2 shrink-0 rounded-full',
                            index === 0 ? 'bg-chart-4' : 'bg-chart-2',
                        )}
                    />
                    <span className="flex flex-col">
                        <span className="text-body text-muted-foreground">
                            {figure.label}
                        </span>
                        <span className="text-card-title font-semibold tabular-nums">
                            {show(figure)}
                        </span>
                    </span>
                </div>
            ))}
            {href && (
                <Button asChild className="mt-auto w-full">
                    <Link href={href}>
                        {t('widgets.full_report')}
                        <ArrowRight />
                    </Link>
                </Button>
            )}
        </>
    );
}

/**
 * Up to two `highlight()` stat widgets as large figures inside Beranda's
 * welcome card (demo "Earnings / Expense"), each loading on its own.
 */
export function WidgetHighlights({ widgets }: { widgets: Meta[] }) {
    const shown = widgets.filter((widget) => widget.highlight).slice(0, 2);

    if (shown.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-x-8 gap-y-4">
            {shown.map((widget, index) => (
                <div
                    key={widget.key}
                    className={cn(
                        'flex min-w-0 flex-col gap-1',
                        index > 0 && 'sm:border-l sm:border-border sm:pl-8',
                    )}
                >
                    <span className="text-body text-muted-foreground">
                        {widget.label}
                    </span>
                    <Deferred
                        data={widget.prop}
                        fallback={<Skeleton className="h-8 w-36" />}
                        rescue={<HighlightFailed widget={widget} />}
                    >
                        <HighlightValue widget={widget} />
                    </Deferred>
                </div>
            ))}
        </div>
    );
}

function HighlightValue({ widget }: { widget: Meta }) {
    const stat = usePage().props[widget.prop] as Stat | undefined;

    if (stat === undefined) {
        return <HighlightFailed widget={widget} />;
    }

    return (
        <span className="flex flex-wrap items-center gap-2.5">
            <span className="text-title tabular-nums">
                {stat.format === 'currency'
                    ? formatCurrency(stat.value)
                    : formatNumber(stat.value)}
            </span>
            {stat.change !== null && <ChangePill change={stat.change} />}
        </span>
    );
}

function HighlightFailed({ widget }: { widget: Meta }) {
    const t = useT();

    return (
        <Button
            variant="outline"
            size="sm"
            className="self-start"
            onClick={() => router.reload({ only: [widget.prop] })}
        >
            <RotateCw />
            {t('widgets.retry')}
        </Button>
    );
}
