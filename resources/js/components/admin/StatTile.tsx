import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { formatCurrency, formatNumber } from '../../lib/format';
import { Skeleton } from '../ui/skeleton';
import { Icon as NamedIcon } from './Icon';

type Props = {
    /** A lucide component, or a lucide name when it arrives as data (widgets). */
    icon: LucideIcon | string;
    label: string;
    value: number;
    format?: 'number' | 'currency';
    /** A StatusBadge next to the value, e.g. "Perlu dicek". */
    badge?: ReactNode;
    loading?: boolean;
};

/** One KPI card of a summary (reference `audit`): icon, label, big number. */
export function StatTile({
    icon: Icon,
    label,
    value,
    format = 'number',
    badge,
    loading = false,
}: Props) {
    return (
        <section
            aria-label={label}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col rounded bg-card p-6 text-card-foreground"
        >
            {loading ? (
                <>
                    <Skeleton className="size-5" />
                    <div className="h-7" />
                    <Skeleton className="h-3 w-28" />
                    <Skeleton className="mt-2.5 h-5.5 w-20" />
                </>
            ) : (
                <>
                    {typeof Icon === 'string' ? (
                        <NamedIcon
                            name={Icon}
                            aria-hidden="true"
                            className="size-5"
                        />
                    ) : (
                        <Icon aria-hidden="true" className="size-5" />
                    )}
                    <div className="h-7" />
                    <div className="text-label text-muted-foreground uppercase">
                        {label}
                    </div>
                    <div className="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                        <span className="text-kpi tabular-nums">
                            {format === 'currency'
                                ? formatCurrency(value)
                                : formatNumber(value)}
                        </span>
                        {badge}
                    </div>
                </>
            )}
        </section>
    );
}
