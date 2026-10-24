import type { ReactElement } from 'react';
import { useSyncExternalStore } from 'react';
import { ResponsiveContainer } from 'recharts';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';

const REDUCED_MOTION = '(prefers-reduced-motion: reduce)';

/** True when the user asked for less motion: charts then draw without animation. */
export function useReducedMotion(): boolean {
    return useSyncExternalStore(
        (notify) => {
            const query = window.matchMedia(REDUCED_MOTION);
            query.addEventListener('change', notify);

            return () => query.removeEventListener('change', notify);
        },
        () => window.matchMedia(REDUCED_MOTION).matches,
        () => false,
    );
}

type Props = {
    /** A recharts chart (BarChart, …); colors come from `fill-chart-*` / `stroke-*` classes only. */
    children: ReactElement;
    /** Names the hidden data table that screen readers get instead of the drawing. */
    title: string;
    /** The plotted values as text rows: [label, value]. */
    rows: [string, string][];
    headers: [string, string];
    className?: string;
};

/**
 * The shadcn `chart` container (frontend-behavior §9): a responsive recharts
 * drawing that screen readers skip, plus the same values as a visually
 * hidden table.
 */
export function ChartContainer({
    children,
    title,
    rows,
    headers,
    className,
}: Props) {
    const t = useT();

    return (
        <>
            <div aria-hidden="true" className={cn('w-full', className)}>
                <ResponsiveContainer>{children}</ResponsiveContainer>
            </div>
            <table className="sr-only">
                <caption>{t('chart.data_table', { title })}</caption>
                <thead>
                    <tr>
                        <th scope="col">{headers[0]}</th>
                        <th scope="col">{headers[1]}</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(([label, value]) => (
                        <tr key={label}>
                            <th scope="row">{label}</th>
                            <td>{value}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}
