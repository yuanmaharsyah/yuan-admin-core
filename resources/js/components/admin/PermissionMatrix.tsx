import { ChevronDown, Search } from 'lucide-react';
import { Fragment, useState } from 'react';
import type { MessageKey } from '../../lib/i18n';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { Checkbox } from '../ui/checkbox';
import { Input } from '../ui/input';
import { UnsavedChangesGuard } from './UnsavedChangesGuard';

type Matrix = Yuan.AdminCore.Data.PermissionMatrixData;
type Row = Yuan.AdminCore.Data.PermissionMatrixRowData;
export type Grants = Record<string, string[]>;

type Props = {
    matrix: Matrix;
    processing: boolean;
    onSave: (grants: Grants) => void;
};

/**
 * The Hak Akses matrix of one level (frontend-behavior §8.2, reference
 * `PermissionMatrix`): grouped rows, row and column toggles, the dependency
 * rule (any action needs View; removing View clears the row; the server
 * enforces it too), changed cells highlighted, and a sticky save bar.
 */
export function PermissionMatrix({ matrix, processing, onSave }: Props) {
    const t = useT();
    const initial: Grants = Object.fromEntries(
        matrix.rows.map((row) => [row.slug, row.granted]),
    );
    const [draft, setDraft] = useState<Grants>(initial);
    const [hint, setHint] = useState<string | null>(null);
    const [query, setQuery] = useState('');
    const [collapsed, setCollapsed] = useState<string[]>([]);

    const has = (slug: string, action: string) =>
        (draft[slug] ?? []).includes(action);
    const changed = (slug: string, action: string) =>
        has(slug, action) !== (initial[slug] ?? []).includes(action);
    const dirtyCount = matrix.rows.reduce(
        (count, row) =>
            count +
            row.actions.filter((action) => changed(row.slug, action)).length,
        0,
    );
    const actionLabel = (action: string) => {
        const key = `matrix.action.${action}` as MessageKey;

        return ['view', 'create', 'edit', 'delete', 'download'].includes(action)
            ? t(key)
            : action.charAt(0).toUpperCase() + action.slice(1);
    };

    /** Sets one row's actions, applying the dependency rule; returns the hint to show. */
    const withRule = (
        next: Grants,
        row: Row,
        actions: string[],
    ): string | null => {
        const before = next[row.slug] ?? [];
        let result = actions.filter((action) => row.actions.includes(action));
        let message: string | null = null;

        if (
            before.includes('view') &&
            !result.includes('view') &&
            result.length > 0
        ) {
            result = [];
            message = t('matrix.hint_row_cleared', { module: row.label });
        } else if (result.length > 0 && !result.includes('view')) {
            const added = result.find((action) => action !== 'view') ?? '';
            result = ['view', ...result];
            message = t('matrix.hint_view_added', {
                module: row.label,
                action: actionLabel(added),
            });
        }

        next[row.slug] = result;

        return message;
    };

    const toggleCell = (row: Row, action: string) => {
        const next = { ...draft };
        const current = next[row.slug] ?? [];
        setHint(
            withRule(
                next,
                row,
                current.includes(action)
                    ? current.filter((item) => item !== action)
                    : [...current, action],
            ),
        );
        setDraft(next);
    };

    const rowState = (row: Row): boolean | 'indeterminate' => {
        const count = row.actions.filter((action) =>
            has(row.slug, action),
        ).length;

        return count === 0
            ? false
            : count === row.actions.length
              ? true
              : 'indeterminate';
    };

    const toggleRow = (row: Row) => {
        setHint(null);
        setDraft({
            ...draft,
            [row.slug]: rowState(row) === true ? [] : [...row.actions],
        });
    };

    const declaring = (action: string) =>
        visibleRows.filter((row) => row.actions.includes(action));
    const columnState = (action: string): boolean | 'indeterminate' => {
        const rows = declaring(action);
        const count = rows.filter((row) => has(row.slug, action)).length;

        return count === 0
            ? false
            : count === rows.length
              ? true
              : 'indeterminate';
    };

    const toggleColumn = (action: string) => {
        const next = { ...draft };
        const on = columnState(action) !== true;

        for (const row of declaring(action)) {
            const current = next[row.slug] ?? [];
            withRule(
                next,
                row,
                on
                    ? [...new Set([...current, action])]
                    : current.filter((item) => item !== action),
            );
        }

        setHint(
            action === 'view'
                ? on
                    ? null
                    : t('matrix.hint_column_cleared')
                : on
                  ? t('matrix.hint_column_view', {
                        action: actionLabel(action),
                    })
                  : null,
        );
        setDraft(next);
    };

    const visibleRows = matrix.rows.filter((row) =>
        row.label.toLowerCase().includes(query.trim().toLowerCase()),
    );
    const groups = [...new Set(visibleRows.map((row) => row.group))];

    return (
        <div className="flex flex-col gap-4">
            <div className="relative flex max-w-70 flex-[1_1_14rem]">
                <Search
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    type="search"
                    aria-label={t('matrix.search_label')}
                    placeholder={t('matrix.search')}
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    className="bg-card pl-8.5"
                />
            </div>
            <section
                aria-label={t('matrix.title')}
                className="overflow-x-auto rounded bg-card text-card-foreground"
            >
                <table className="w-full min-w-3xl border-collapse text-table">
                    <thead>
                        <tr className="h-10 border-b border-rule text-label text-muted-foreground uppercase">
                            <th scope="col" className="px-4 text-left">
                                {t('matrix.module')}
                            </th>
                            {matrix.columns.map((action) => (
                                <th
                                    key={action}
                                    scope="col"
                                    className="w-23 text-center"
                                >
                                    {actionLabel(action)}
                                </th>
                            ))}
                        </tr>
                        <tr className="h-row border-b border-border">
                            <td className="px-4 text-muted-foreground">
                                {t('matrix.all_modules')}
                            </td>
                            {matrix.columns.map((action) => (
                                <td key={action} className="text-center">
                                    {declaring(action).length > 0 && (
                                        <Checkbox
                                            aria-label={t('matrix.all_of', {
                                                action: actionLabel(action),
                                            })}
                                            checked={columnState(action)}
                                            onCheckedChange={() =>
                                                toggleColumn(action)
                                            }
                                        />
                                    )}
                                </td>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {groups.map((group) => {
                            const rows = visibleRows.filter(
                                (row) => row.group === group,
                            );
                            const open = !collapsed.includes(group);

                            return (
                                <Fragment key={group}>
                                    <tr className="h-10 border-b border-border bg-background">
                                        <td
                                            colSpan={matrix.columns.length + 1}
                                            className="p-0"
                                        >
                                            <button
                                                type="button"
                                                aria-expanded={open}
                                                onClick={() =>
                                                    setCollapsed(
                                                        open
                                                            ? [
                                                                  ...collapsed,
                                                                  group,
                                                              ]
                                                            : collapsed.filter(
                                                                  (item) =>
                                                                      item !==
                                                                      group,
                                                              ),
                                                    )
                                                }
                                                className="flex h-10 items-center gap-2 px-3 font-semibold"
                                            >
                                                <ChevronDown
                                                    aria-hidden="true"
                                                    className={cn(
                                                        'size-4 transition-transform',
                                                        !open && '-rotate-90',
                                                    )}
                                                />
                                                {group}
                                                <span className="text-xs font-normal text-muted-foreground">
                                                    {rows.length}
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                    {open &&
                                        rows.map((row) => (
                                            <tr
                                                key={row.slug}
                                                className="h-row border-b border-border"
                                            >
                                                <th
                                                    scope="row"
                                                    className="pr-4 pl-9 text-left font-medium"
                                                >
                                                    <span className="flex items-center gap-2.5">
                                                        <Checkbox
                                                            aria-label={t(
                                                                'matrix.row_all',
                                                                {
                                                                    module: row.label,
                                                                },
                                                            )}
                                                            checked={rowState(
                                                                row,
                                                            )}
                                                            onCheckedChange={() =>
                                                                toggleRow(row)
                                                            }
                                                        />
                                                        {row.label}
                                                    </span>
                                                </th>
                                                {matrix.columns.map(
                                                    (action) => (
                                                        <td
                                                            key={action}
                                                            title={
                                                                changed(
                                                                    row.slug,
                                                                    action,
                                                                )
                                                                    ? t(
                                                                          'matrix.unsaved',
                                                                      )
                                                                    : undefined
                                                            }
                                                            className={cn(
                                                                'text-center',
                                                                changed(
                                                                    row.slug,
                                                                    action,
                                                                ) &&
                                                                    'bg-highlight/45',
                                                            )}
                                                        >
                                                            {row.actions.includes(
                                                                action,
                                                            ) ? (
                                                                <Checkbox
                                                                    aria-label={t(
                                                                        'matrix.cell',
                                                                        {
                                                                            module: row.label,
                                                                            action: actionLabel(
                                                                                action,
                                                                            ),
                                                                        },
                                                                    )}
                                                                    checked={has(
                                                                        row.slug,
                                                                        action,
                                                                    )}
                                                                    onCheckedChange={() =>
                                                                        toggleCell(
                                                                            row,
                                                                            action,
                                                                        )
                                                                    }
                                                                />
                                                            ) : (
                                                                <span
                                                                    aria-label={t(
                                                                        'matrix.not_declared',
                                                                    )}
                                                                    className="text-muted-foreground"
                                                                >
                                                                    —
                                                                </span>
                                                            )}
                                                        </td>
                                                    ),
                                                )}
                                            </tr>
                                        ))}
                                </Fragment>
                            );
                        })}
                    </tbody>
                </table>
                {visibleRows.length === 0 && (
                    <div
                        role="status"
                        className="px-4 py-10 text-center text-body text-muted-foreground"
                    >
                        {t('matrix.no_match', { query })}
                    </div>
                )}
            </section>
            {dirtyCount > 0 && (
                <div
                    role="status"
                    className="sticky bottom-0 z-5 flex flex-wrap items-center gap-3 rounded border border-border bg-card px-4 py-3"
                >
                    <div className="flex min-w-0 flex-[1_1_15rem] flex-col gap-0.5">
                        <strong className="text-body font-semibold tabular-nums">
                            {t('matrix.dirty', { count: dirtyCount })}
                        </strong>
                        {hint && (
                            <span className="text-table text-muted-foreground">
                                {hint}
                            </span>
                        )}
                    </div>
                    <Button
                        variant="outline"
                        className="bg-transparent font-semibold"
                        onClick={() => {
                            setDraft(initial);
                            setHint(null);
                        }}
                    >
                        {t('matrix.discard')}
                    </Button>
                    <Button
                        className="font-semibold"
                        loading={processing}
                        onClick={() => onSave(draft)}
                    >
                        {t('matrix.save')}
                    </Button>
                </div>
            )}
            <UnsavedChangesGuard when={dirtyCount > 0 && !processing} />
        </div>
    );
}
