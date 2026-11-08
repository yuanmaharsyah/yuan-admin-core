import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatNumber } from '../../lib/format';
import { cn } from '../../lib/utils';
import { ChartContainer, useReducedMotion } from '../ui/chart';
import { Skeleton } from '../ui/skeleton';

/** `n`: an optional second series drawn below zero (a positive number); `c`: one drawn beside each bar. */
type Point = { x: string; y: number; n?: number; c?: number };

type Props = {
    title: string;
    /** Next to the title, e.g. the date range. */
    subtitle?: ReactNode;
    data: Point[];
    /** Label of an x value in the tooltip and the hidden table. */
    xFormat: (x: string) => string;
    /** Label of an x value on the axis (default `xFormat`), e.g. a short date. */
    tickFormat?: (x: string) => string;
    /** The tooltip of a bar: a bold line and a muted line (`n`: the value below zero). */
    tooltip: (
        x: string,
        y: number,
        n?: number,
    ) => { title: string; detail: string };
    /** Legend names of the bars and the `c` series, shown under the chart. */
    legend?: [string, string];
    /** A side panel next to the chart (≥1280px; below it on smaller screens), e.g. totals. */
    aside?: ReactNode;
    /** Column header of the values in the hidden table. */
    valueLabel: string;
    onSelect?: (x: string) => void;
    loading?: boolean;
};

/** Skeleton bar heights (literal classes, so Tailwind sees them). */
const SKELETON = ['h-1/2', 'h-3/5', 'h-1/3', 'h-3/4', 'h-2/5', 'h-2/3'];

/**
 * A bar per day or week (demo "Revenue Updates"): ink bars (a second series
 * below zero when points carry `n`), an optional side panel; the
 * hovered one in the highlight color with a tooltip; clicking a bar selects
 * it. Scrolls sideways on phones.
 */
export function BarChartCard({
    title,
    subtitle,
    data,
    xFormat,
    tickFormat = xFormat,
    tooltip,
    valueLabel,
    onSelect,
    aside,
    legend,
    loading = false,
}: Props) {
    const reduced = useReducedMotion();
    const [active, setActive] = useState<number | null>(null);
    const every = Math.max(1, Math.ceil(data.length / 8));
    const twoSided = data.some((point) => point.n !== undefined);
    const plotted = data.map((point) => ({
        ...point,
        below: point.n === undefined ? undefined : -point.n,
    }));

    return (
        <section
            aria-label={title}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col gap-3 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <div className="flex flex-wrap items-baseline gap-x-4 gap-y-2">
                <h2 className="flex-1 text-card-title">{title}</h2>
                {subtitle && (
                    <span className="text-table text-muted-foreground tabular-nums [&>a]:text-foreground">
                        {subtitle}
                    </span>
                )}
            </div>
            {loading ? (
                <div
                    aria-hidden="true"
                    className="flex h-74 items-end gap-1.5 pt-16 pb-6 pl-9"
                >
                    {Array.from({ length: 30 }, (_, index) => (
                        <Skeleton
                            key={index}
                            className={cn(
                                'flex-1 rounded-b-none',
                                SKELETON[index % SKELETON.length],
                            )}
                        />
                    ))}
                </div>
            ) : (
                <div className="flex flex-col gap-6 xl:flex-row">
                    <div className="min-w-0 flex-1 overflow-x-auto overflow-y-hidden">
                        <ChartContainer
                            title={title}
                            headers={[title, valueLabel]}
                            rows={data.map((point) => [
                                xFormat(point.x),
                                point.n === undefined
                                    ? formatNumber(point.y)
                                    : `${formatNumber(point.y)} / −${formatNumber(point.n)}`,
                            ])}
                            className="h-80 min-w-180 lg:min-w-0"
                        >
                            <BarChart
                                data={plotted}
                                stackOffset={twoSided ? 'sign' : undefined}
                                margin={{
                                    top: 48,
                                    right: 0,
                                    bottom: 0,
                                    left: 0,
                                }}
                                onMouseLeave={() => setActive(null)}
                                onMouseMove={(state) =>
                                    setActive(
                                        state.activeTooltipIndex ===
                                            undefined ||
                                            state.activeTooltipIndex === null
                                            ? null
                                            : Number(state.activeTooltipIndex),
                                    )
                                }
                                onClick={(state) => {
                                    const index = Number(
                                        state.activeTooltipIndex,
                                    );

                                    if (onSelect && data[index]) {
                                        onSelect(data[index].x);
                                    }
                                }}
                            >
                                <CartesianGrid
                                    vertical={false}
                                    className="stroke-border"
                                />
                                <XAxis
                                    dataKey="x"
                                    tickLine={false}
                                    axisLine={false}
                                    interval={every - 1}
                                    tickFormatter={tickFormat}
                                    className="text-xs [&_text]:fill-muted-foreground"
                                />
                                <YAxis
                                    width={36}
                                    tickLine={false}
                                    axisLine={false}
                                    allowDecimals={false}
                                    tickFormatter={(value: number) =>
                                        formatNumber(value)
                                    }
                                    className="text-xs [&_text]:fill-muted-foreground"
                                />
                                <Tooltip
                                    cursor={false}
                                    isAnimationActive={!reduced}
                                    content={({ active: shown, payload }) => {
                                        const point = payload?.[0]?.payload as
                                            Point | undefined;

                                        if (!shown || !point) {
                                            return null;
                                        }

                                        const tip = tooltip(
                                            point.x,
                                            point.y,
                                            point.n,
                                        );

                                        return (
                                            <div className="flex flex-col gap-0.5 rounded-lg border border-border bg-popover px-3 py-2 text-popover-foreground shadow-popover">
                                                <strong className="text-body font-semibold tabular-nums">
                                                    {tip.title}
                                                </strong>
                                                <span className="text-xs text-muted-foreground">
                                                    {tip.detail}
                                                </span>
                                            </div>
                                        );
                                    }}
                                />
                                <Bar
                                    dataKey="y"
                                    stackId={twoSided ? 'sign' : undefined}
                                    radius={[6, 6, 6, 6]}
                                    maxBarSize={14}
                                    isAnimationActive={!reduced}
                                    className={
                                        onSelect ? 'cursor-pointer' : undefined
                                    }
                                >
                                    {data.map((point, index) => (
                                        <Cell
                                            key={point.x}
                                            className={
                                                index === active
                                                    ? 'fill-highlight'
                                                    : 'fill-chart-2'
                                            }
                                        />
                                    ))}
                                </Bar>
                                {data.some(
                                    (point) => point.c !== undefined,
                                ) && (
                                    <Bar
                                        dataKey="c"
                                        radius={[6, 6, 6, 6]}
                                        maxBarSize={14}
                                        isAnimationActive={!reduced}
                                        className="fill-chart-4"
                                    />
                                )}
                                {twoSided && (
                                    <Bar
                                        dataKey="below"
                                        stackId="sign"
                                        radius={[6, 6, 6, 6]}
                                        maxBarSize={14}
                                        isAnimationActive={!reduced}
                                        className="fill-chart-4"
                                    />
                                )}
                            </BarChart>
                        </ChartContainer>
                        {legend && (
                            <ul className="m-0 mt-3 flex list-none justify-center gap-5 p-0 text-table text-muted-foreground">
                                {legend.map((name, index) => (
                                    <li
                                        key={name}
                                        className="flex items-center gap-2"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className={cn(
                                                'size-2 rounded-sm',
                                                index === 0
                                                    ? 'bg-chart-2'
                                                    : 'bg-chart-4',
                                            )}
                                        />
                                        {name}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                    {aside && (
                        <div className="flex shrink-0 flex-col gap-5 xl:w-64">
                            {aside}
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}
