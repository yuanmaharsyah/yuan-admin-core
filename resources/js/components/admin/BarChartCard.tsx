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

type Point = { x: string; y: number };

type Props = {
    title: string;
    /** Next to the title, e.g. the date range. */
    subtitle?: ReactNode;
    data: Point[];
    /** Label of an x value on the axis and in the hidden table. */
    xFormat: (x: string) => string;
    /** The tooltip of a bar: a bold line and a muted line. */
    tooltip: (x: string, y: number) => { title: string; detail: string };
    /** Column header of the values in the hidden table. */
    valueLabel: string;
    onSelect?: (x: string) => void;
    loading?: boolean;
};

/** Skeleton bar heights (literal classes, so Tailwind sees them). */
const SKELETON = ['h-1/2', 'h-3/5', 'h-1/3', 'h-3/4', 'h-2/5', 'h-2/3'];

/**
 * A bar per day or week (reference "Aktivitas per Hari"): ink bars, the
 * hovered one in the highlight color with a tooltip; clicking a bar selects
 * it. Scrolls sideways on phones.
 */
export function BarChartCard({
    title,
    subtitle,
    data,
    xFormat,
    tooltip,
    valueLabel,
    onSelect,
    loading = false,
}: Props) {
    const reduced = useReducedMotion();
    const [active, setActive] = useState<number | null>(null);
    const every = Math.max(1, Math.ceil(data.length / 8));

    return (
        <section
            aria-label={title}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col gap-3 rounded bg-card p-6 text-card-foreground"
        >
            <div className="flex flex-wrap items-baseline gap-x-4 gap-y-2 border-b-2 border-rule pb-3">
                <h2 className="flex-1 text-card-title">{title}</h2>
                {subtitle && (
                    <span className="text-table text-muted-foreground tabular-nums">
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
                <div className="overflow-x-auto overflow-y-hidden">
                    <ChartContainer
                        title={title}
                        headers={[title, valueLabel]}
                        rows={data.map((point) => [
                            xFormat(point.x),
                            formatNumber(point.y),
                        ])}
                        className="h-80 min-w-180 lg:min-w-0"
                    >
                        <BarChart
                            data={data}
                            margin={{ top: 48, right: 0, bottom: 0, left: 0 }}
                            onMouseLeave={() => setActive(null)}
                            onMouseMove={(state) =>
                                setActive(
                                    state.activeTooltipIndex === undefined ||
                                        state.activeTooltipIndex === null
                                        ? null
                                        : Number(state.activeTooltipIndex),
                                )
                            }
                            onClick={(state) => {
                                const index = Number(state.activeTooltipIndex);

                                if (onSelect && data[index]) {
                                    onSelect(data[index].x);
                                }
                            }}
                        >
                            <CartesianGrid
                                vertical={false}
                                strokeDasharray="1 3"
                                className="stroke-border"
                            />
                            <XAxis
                                dataKey="x"
                                tickLine={false}
                                interval={every - 1}
                                tickFormatter={xFormat}
                                className="text-xs [&_line]:stroke-foreground [&_text]:fill-muted-foreground"
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

                                    const tip = tooltip(point.x, point.y);

                                    return (
                                        <div className="flex flex-col gap-0.5 rounded border border-border bg-popover px-3 py-2 text-popover-foreground shadow-popover">
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
                                radius={[2, 2, 0, 0]}
                                maxBarSize={28}
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
                        </BarChart>
                    </ChartContainer>
                </div>
            )}
        </section>
    );
}
