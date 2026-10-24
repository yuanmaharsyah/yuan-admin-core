import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
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
            className="flex min-w-0 flex-col gap-3 rounded bg-card p-6 text-card-foreground"
        >
            <h2 className="border-b-2 border-rule pb-3 text-card-title">
                {title}
            </h2>
            {rows.length === 0 ? (
                <p className="m-0 text-table text-muted-foreground">{empty}</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-xs">
                        <thead>
                            <tr className="border-b border-border text-left text-label text-muted-foreground uppercase">
                                {columns.map((column) => (
                                    <th
                                        key={column.value}
                                        scope="col"
                                        className="py-2 pr-3"
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
                                            className="py-2 pr-3"
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

/** "Lihat semua ›" in a widget card. */
export function SeeAll({ href, label }: { href: string; label: string }) {
    return (
        <Link
            href={href}
            className="inline-flex items-center gap-0.5 text-table font-medium whitespace-nowrap hover:underline"
        >
            {label}
            <ChevronRight aria-hidden="true" className="size-4" />
        </Link>
    );
}
