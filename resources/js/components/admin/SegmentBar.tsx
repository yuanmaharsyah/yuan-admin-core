import { formatNumber } from '../../lib/format';
import { cn } from '../../lib/utils';
import { Skeleton } from '../ui/skeleton';

type Segment = { key: string; label: string; value: number };

type Props = {
    title: string;
    segments: Segment[];
    /** The pressed segment; the others fade. */
    selected?: string | null;
    onSelect?: (key: string) => void;
    loading?: boolean;
    /** Accessible name of each figure's button, e.g. "Filter event Dibuat, 5.210". */
    selectLabel?: (segment: Segment) => string;
};

/** Colors in order, from the chart tokens (frontend-behavior §9). */
const FILLS = [
    'fill-chart-1',
    'fill-chart-2',
    'fill-chart-3',
    'fill-chart-4',
    'fill-chart-5',
];
const MARKS = [
    'bg-chart-1',
    'bg-chart-2',
    'bg-chart-3',
    'bg-chart-4',
    'bg-chart-5',
];

/**
 * Shares of a total as one stacked bar with the figures above it (reference
 * "Aktivitas per Jenis"). A figure or a bar segment is a toggle when
 * `onSelect` is given.
 */
export function SegmentBar({
    title,
    segments,
    selected = null,
    onSelect,
    loading = false,
    selectLabel,
}: Props) {
    const total = segments.reduce((sum, segment) => sum + segment.value, 0);
    let x = 0;

    return (
        <section
            aria-label={title}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col gap-5 rounded bg-card p-6 text-card-foreground"
        >
            <h2 className="border-b-2 border-rule pb-3 text-card-title">
                {title}
            </h2>
            {loading ? (
                <>
                    <div className="flex flex-wrap gap-x-6 gap-y-4">
                        {segments.map((segment) => (
                            <div
                                key={segment.key}
                                className="flex flex-1 basis-24 flex-col gap-2"
                            >
                                <Skeleton className="h-5 w-16" />
                                <Skeleton className="h-3 w-18" />
                            </div>
                        ))}
                    </div>
                    <Skeleton className="h-10 w-full" />
                </>
            ) : (
                <>
                    <div className="flex flex-wrap gap-x-6 gap-y-4">
                        {segments.map((segment, index) => (
                            <button
                                key={segment.key}
                                type="button"
                                disabled={!onSelect}
                                aria-pressed={
                                    onSelect
                                        ? selected === segment.key
                                        : undefined
                                }
                                aria-label={selectLabel?.(segment)}
                                onClick={() => onSelect?.(segment.key)}
                                className={cn(
                                    'flex gap-2.5 rounded text-left focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring disabled:cursor-default',
                                    selected !== null &&
                                        selected !== segment.key &&
                                        'opacity-40',
                                )}
                            >
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'w-0.75 shrink-0 self-stretch',
                                        MARKS[index % MARKS.length],
                                        index % MARKS.length === 4 &&
                                            'outline outline-border',
                                    )}
                                />
                                <span className="flex flex-col gap-0.5">
                                    <span className="text-section tabular-nums">
                                        {formatNumber(segment.value)}
                                    </span>
                                    <span className="text-label text-muted-foreground uppercase">
                                        {segment.label}
                                    </span>
                                </span>
                            </button>
                        ))}
                    </div>
                    <svg
                        aria-hidden="true"
                        className="h-10 w-full overflow-hidden rounded"
                        viewBox={`0 0 ${Math.max(total, 1)} 40`}
                        preserveAspectRatio="none"
                    >
                        {total === 0 ? (
                            <rect
                                width="1"
                                height="40"
                                className="fill-muted"
                            />
                        ) : (
                            segments.map((segment, index) => {
                                const start = x;
                                x += segment.value;

                                return (
                                    <rect
                                        key={segment.key}
                                        x={start}
                                        width={segment.value}
                                        height="40"
                                        vectorEffect="non-scaling-stroke"
                                        strokeWidth="2"
                                        onClick={() => onSelect?.(segment.key)}
                                        className={cn(
                                            FILLS[index % FILLS.length],
                                            'stroke-card',
                                            onSelect && 'cursor-pointer',
                                            selected !== null &&
                                                selected !== segment.key &&
                                                'opacity-30',
                                        )}
                                    />
                                );
                            })
                        )}
                    </svg>
                </>
            )}
        </section>
    );
}
