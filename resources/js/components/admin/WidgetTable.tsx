import { Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight } from 'lucide-react';
import { useT } from '../../lib/i18n';

type Props = {
    title: string;
    columns: Yuan.AdminCore.Data.OptionData[];
    /** At most five rows; the server cuts the rest. */
    rows: Record<string, string | null>[];
    href?: string | null;
    empty: string;
};

/** A compact table card on Beranda (ui-design §4.8): no paging, a "Lihat semua" footer. */
export function WidgetTable({ title, columns, rows, href, empty }: Props) {
    const t = useT();

    return (
        <section
            aria-label={title}
            className="flex min-w-0 flex-col gap-3 rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <h2 className="text-card-title">{title}</h2>
            {rows.length === 0 ? (
                <p className="m-0 text-table text-muted-foreground">{empty}</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-table">
                        <thead>
                            <tr className="border-b border-border text-left font-medium text-muted-foreground">
                                {columns.map((column) => (
                                    <th
                                        key={column.value}
                                        scope="col"
                                        className="py-2.5 pr-3 font-medium"
                                    >
                                        {column.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => (
                                <tr
                                    key={index}
                                    className="border-b border-border last:border-0"
                                >
                                    {columns.map((column) => (
                                        <td
                                            key={column.value}
                                            className="py-2.5 pr-3"
                                        >
                                            {row[column.value] ?? '—'}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            {href && <SeeAll href={href} label={t('widgets.see_all')} />}
        </section>
    );
}

/** "Lihat semua →" in a widget card: a small outline button, or (`compact`) a round icon button for narrow cards. */
export function SeeAll({
    href,
    label,
    compact = false,
}: {
    href: string;
    label: string;
    compact?: boolean;
}) {
    if (compact) {
        return (
            <Link
                href={href}
                aria-label={label}
                title={label}
                className="flex size-control shrink-0 items-center justify-center rounded-full border border-input bg-background transition-colors duration-150 hover:bg-accent dark:bg-input/30 dark:hover:bg-input/50"
            >
                <ArrowUpRight aria-hidden="true" className="size-4" />
            </Link>
        );
    }

    return (
        <Link
            href={href}
            className="inline-flex h-control-sm shrink-0 items-center gap-1.5 self-start rounded border border-input bg-background px-3 text-table font-medium whitespace-nowrap transition-colors duration-150 hover:bg-accent dark:bg-input/30 dark:hover:bg-input/50"
        >
            {label}
            <ArrowRight aria-hidden="true" className="size-4" />
        </Link>
    );
}
