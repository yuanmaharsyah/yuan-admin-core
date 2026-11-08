import type { ReactNode } from 'react';
import { useId } from 'react';
import { Area, AreaChart, Tooltip } from 'recharts';
import { formatCurrency, formatNumber } from '../../lib/format';
import { ChartContainer, useReducedMotion } from '../ui/chart';
import { Skeleton } from '../ui/skeleton';
import { CardHeading } from './CardHeading';
import { ChangeLine } from './ChangePill';

type Point = { x: string; y: number };

type Props = {
    title: string;
    value: number;
    format?: 'number' | 'currency';
    change?: number | null;
    caption?: string | null;
    data: Point[];
    /** Label of an x value in the tooltip and the hidden table. */
    xFormat: (x: string) => string;
    action?: ReactNode;
    loading?: boolean;
};

/** A headline figure with its change over a soft gradient area, no axes (demo "Monthly earnings"). */
export function AreaChartCard({
    title,
    value,
    format = 'number',
    change,
    caption,
    data,
    xFormat,
    action,
    loading = false,
}: Props) {
    const reduced = useReducedMotion();
    const gradient = useId().replace(/:/g, '');
    const show = (n: number) =>
        format === 'currency' ? formatCurrency(n) : formatNumber(n);

    return (
        <section
            aria-label={title}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col gap-4 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <CardHeading title={title} action={action} />
            {loading ? (
                <>
                    <Skeleton className="h-7 w-32" />
                    <Skeleton className="h-24 w-full" />
                </>
            ) : (
                <>
                    <div className="flex flex-col gap-1.5">
                        <span className="text-kpi tabular-nums">
                            {show(value)}
                        </span>
                        <ChangeLine change={change} caption={caption} />
                    </div>
                    <ChartContainer
                        title={title}
                        headers={[title, title]}
                        rows={data.map((point) => [
                            xFormat(point.x),
                            show(point.y),
                        ])}
                        className="mt-auto h-28 text-foreground"
                    >
                        <AreaChart
                            data={data}
                            margin={{ top: 4, right: 0, bottom: 0, left: 0 }}
                        >
                            <defs>
                                <linearGradient
                                    id={gradient}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stopColor="currentColor"
                                        stopOpacity={0.35}
                                    />
                                    <stop
                                        offset="100%"
                                        stopColor="currentColor"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                            </defs>
                            <Tooltip
                                cursor={false}
                                isAnimationActive={!reduced}
                                content={({ active, payload }) => {
                                    const point = payload?.[0]?.payload as
                                        Point | undefined;

                                    return active && point ? (
                                        <div className="flex flex-col gap-0.5 rounded-lg border border-border bg-popover px-3 py-2 text-popover-foreground shadow-popover">
                                            <strong className="text-body font-semibold tabular-nums">
                                                {show(point.y)}
                                            </strong>
                                            <span className="text-xs text-muted-foreground">
                                                {xFormat(point.x)}
                                            </span>
                                        </div>
                                    ) : null;
                                }}
                            />
                            <Area
                                dataKey="y"
                                type="monotone"
                                strokeWidth={2}
                                stroke="currentColor"
                                fill={`url(#${gradient})`}
                                isAnimationActive={!reduced}
                            />
                        </AreaChart>
                    </ChartContainer>
                </>
            )}
        </section>
    );
}
