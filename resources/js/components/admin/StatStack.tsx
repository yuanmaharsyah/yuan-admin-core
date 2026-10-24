import type { ReactNode } from 'react';
import { formatNumber } from '../../lib/format';
import { Skeleton } from '../ui/skeleton';

type Item = { label: string; value: number };

type Props = {
    label: string;
    items: Item[];
    /** Below the figures, pushed to the bottom (e.g. a small chart). */
    children?: ReactNode;
    loading?: boolean;
};

/** A column of small figures separated by rules, with an optional footer (reference `audit`). */
export function StatStack({ label, items, children, loading = false }: Props) {
    return (
        <section
            aria-label={label}
            aria-busy={loading || undefined}
            className="flex min-w-0 flex-col rounded bg-card p-6 text-card-foreground"
        >
            {items.map((item, index) => (
                <div
                    key={item.label}
                    className={
                        index === 0
                            ? 'flex flex-col gap-1 pb-4'
                            : 'flex flex-col gap-1 border-t border-border py-4'
                    }
                >
                    {loading ? (
                        <>
                            <Skeleton className="h-3 w-30" />
                            <Skeleton className="mt-1.5 h-5.5 w-18" />
                        </>
                    ) : (
                        <>
                            <div className="text-label text-muted-foreground uppercase">
                                {item.label}
                            </div>
                            <div className="text-kpi tabular-nums">
                                {formatNumber(item.value)}
                            </div>
                        </>
                    )}
                </div>
            ))}
            <div className="min-h-6 flex-1" />
            {children}
        </section>
    );
}
