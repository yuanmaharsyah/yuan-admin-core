import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';

type Row = Yuan.AdminCore.Data.Audit.DiffRowData;

const LONG = 80;

/**
 * Changed fields of an audit row (reference `DiffTable`): before struck
 * through, after in full; masked values in mono; long values (JSON) fold
 * open on demand.
 */
export function DiffTable({ rows }: { rows: Row[] }) {
    const t = useT();

    return (
        <table
            aria-label={t('diff.label')}
            className="w-full text-table tabular-nums"
        >
            <thead>
                <tr className="border-b border-rule text-left">
                    <th
                        scope="col"
                        className="w-1/4 py-2 pr-3 text-label text-muted-foreground uppercase"
                    >
                        {t('diff.field')}
                    </th>
                    <th
                        scope="col"
                        className="py-2 pr-3 text-label text-muted-foreground uppercase"
                    >
                        {t('diff.before')}
                    </th>
                    <th
                        scope="col"
                        className="py-2 text-label text-muted-foreground uppercase"
                    >
                        {t('diff.after')}
                    </th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => (
                    <tr
                        key={row.field}
                        className="border-b border-border align-top last:border-b-0"
                    >
                        <th
                            scope="row"
                            className="py-2 pr-3 text-left font-medium"
                        >
                            {row.field}
                        </th>
                        <td
                            className={cn(
                                'py-2 pr-3 text-muted-foreground',
                                row.old !== null && 'line-through',
                                row.masked && 'font-mono',
                            )}
                        >
                            <Value value={row.old} />
                        </td>
                        <td className={cn('py-2', row.masked && 'font-mono')}>
                            <Value value={row.new} />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function Value({ value }: { value: string | null }) {
    const t = useT();

    if (value === null) {
        return <>—</>;
    }

    if (value.length <= LONG && !value.includes('\n')) {
        return <span className="break-words">{value}</span>;
    }

    return (
        <details>
            <summary className="cursor-pointer">
                {value.slice(0, 40).replace(/\s+/g, ' ')}… {t('diff.show')}
            </summary>
            <pre className="mt-1 max-h-60 overflow-auto rounded bg-muted p-2 font-mono text-xs whitespace-pre-wrap">
                {value}
            </pre>
        </details>
    );
}
