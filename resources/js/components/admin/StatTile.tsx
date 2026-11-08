import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { formatCurrency, formatNumber } from '../../lib/format';
import { Skeleton } from '../ui/skeleton';
import { ChangeLine } from './ChangePill';
import { Icon as NamedIcon } from './Icon';

type Props = {
    /** A lucide component, or a lucide name when it arrives as data (widgets). */
    icon: LucideIcon | string;
    label: string;
    value: number;
    format?: 'number' | 'currency';
    /** A StatusBadge next to the value, e.g. "Perlu dicek". */
    badge?: ReactNode;
    /** Percent against the previous period, shown as a green / red pill. */
    change?: number | null;
    /** Text after the pill, e.g. "dibanding periode sebelumnya". */
    caption?: string | null;
    /** Below the value, e.g. the widget's "Lihat semua →" button. */
    action?: ReactNode;
    loading?: boolean;
};

/** One KPI card: title with a round icon on the right, the big number, an optional action. */
export function StatTile({
    icon: Icon,
    label,
    value,
    format = 'number',
    badge,
    change,
    caption,
    action,
    loading = false,
}: Props) {
    return (
        <section
            aria-label={label}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col gap-4 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <div className="flex items-start gap-3">
                <div className="min-w-0 flex-1 text-card-title">
                    {loading ? <Skeleton className="mt-1.5 h-4 w-28" /> : label}
                </div>
                <span
                    aria-hidden="true"
                    className="flex size-11 shrink-0 items-center justify-center rounded-full border border-border"
                >
                    {loading ? (
                        <Skeleton className="size-5 rounded-full" />
                    ) : typeof Icon === 'string' ? (
                        <NamedIcon name={Icon} className="size-5" />
                    ) : (
                        <Icon className="size-5" />
                    )}
                </span>
            </div>
            {loading ? (
                <Skeleton className="h-8 w-24" />
            ) : (
                <div className="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                    <span className="text-kpi tabular-nums">
                        {format === 'currency'
                            ? formatCurrency(value)
                            : formatNumber(value)}
                    </span>
                    {badge}
                </div>
            )}
            {!loading && <ChangeLine change={change} caption={caption} />}
            {action && !loading && <div className="mt-auto">{action}</div>}
        </section>
    );
}
