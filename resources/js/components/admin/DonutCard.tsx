import type { ReactNode } from 'react';
import { Cell, Pie, PieChart } from 'recharts';
import { formatCurrency, formatNumber } from '../../lib/format';
import { cn } from '../../lib/utils';
import { ChartContainer, useReducedMotion } from '../ui/chart';
import { Skeleton } from '../ui/skeleton';
import { CardHeading } from './CardHeading';
import { ChangeLine } from './ChangePill';

type Segment = { key: string; label: string; value: number };

type Props = {
    title: string;
    value: number;
    format?: 'number' | 'currency';
    change?: number | null;
    caption?: string | null;
    segments: Segment[];
    onSelect?: (key: string) => void;
    action?: ReactNode;
    loading?: boolean;
};

/** Literal classes, so Tailwind sees them: chart-1 (brand) first, then ink and greys. */
const FILLS = [
    'fill-chart-1',
    'fill-chart-2',
    'fill-chart-3',
    'fill-chart-4',
    'fill-chart-5',
];
const DOTS = [
    'bg-chart-1',
    'bg-chart-2',
    'bg-chart-3',
    'bg-chart-4',
    'bg-chart-5',
];

/** A headline figure with its change, a ring of segments and a legend (demo "Yearly Backup"). */
export function DonutCard({
    title,
    value,
    format = 'number',
    change,
    caption,
    segments,
    onSelect,
    action,
    loading = false,
}: Props) {
    const reduced = useReducedMotion();
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
                <div className="flex items-center gap-4">
                    <div className="flex flex-1 flex-col gap-2">
                        <Skeleton className="h-7 w-28" />
                        <Skeleton className="h-4 w-36" />
                    </div>
                    <Skeleton className="size-28 rounded-full" />
                </div>
            ) : (
                <>
                    <div className="flex items-center gap-4">
                        <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                            <span className="text-kpi tabular-nums">
                                {show(value)}
                            </span>
                            <ChangeLine change={change} caption={caption} />
                        </div>
                        <ChartContainer
                            title={title}
                            headers={[title, title]}
                            rows={segments.map((segment) => [
                                segment.label,
                                show(segment.value),
                            ])}
                            className="size-28 shrink-0"
                        >
                            <PieChart>
                                <Pie
                                    data={segments}
                                    dataKey="value"
                                    nameKey="label"
                                    innerRadius="68%"
                                    outerRadius="100%"
                                    paddingAngle={2}
                                    cornerRadius={4}
                                    stroke="none"
                                    isAnimationActive={!reduced}
                                    onClick={(_, index) => {
                                        const segment = segments[index];

                                        if (segment) {
                                            onSelect?.(segment.key);
                                        }
                                    }}
                                    className={
                                        onSelect ? 'cursor-pointer' : undefined
                                    }
                                >
                                    {segments.map((segment, index) => (
                                        <Cell
                                            key={segment.key}
                                            className={
                                                FILLS[index % FILLS.length]
                                            }
                                        />
                                    ))}
                                </Pie>
                            </PieChart>
                        </ChartContainer>
                    </div>
                    <ul className="m-0 flex list-none flex-wrap gap-x-5 gap-y-2 p-0">
                        {segments.map((segment, index) => (
                            <li key={segment.key}>
                                <button
                                    type="button"
                                    disabled={!onSelect}
                                    onClick={() => onSelect?.(segment.key)}
                                    className="flex items-center gap-2 rounded text-body disabled:cursor-default"
                                >
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'size-2 rounded-full',
                                            DOTS[index % DOTS.length],
                                        )}
                                    />
                                    <span className="text-muted-foreground">
                                        {segment.label}
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        {show(segment.value)}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
