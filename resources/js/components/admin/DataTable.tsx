import { Link, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    AlertCircle,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    MoreHorizontal,
    SearchX,
    Trash2,
} from 'lucide-react';
import type { MouseEvent, ReactNode } from 'react';
import { Fragment, useState } from 'react';
import { useAction } from '../../hooks/useAction';
import { useTableQuery } from '../../hooks/useTableQuery';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { Checkbox } from '../ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { ConfirmDialog } from './ConfirmDialog';
import { DataTablePagination } from './DataTablePagination';
import { DataTableToolbar } from './DataTableToolbar';
import { EmptyState } from './EmptyState';
import type { EmptyStateProps } from './EmptyState';

/** A column of `DataTable<T>`; `key` must be a property of the row type. */
export type DataTableColumn<T> = {
    key: Extract<keyof T, string>;
    label: string;
    /** Defaults to the value as text. */
    cell?: (row: T) => ReactNode;
    align?: 'left' | 'right';
    /** Width and text styles of the column's cells, e.g. `w-40 text-muted-foreground`. */
    className?: string;
    /** False keeps the column out of the "Kolom" menu. */
    hideable?: boolean;
};

export type RowAction = {
    label: string;
    icon?: LucideIcon;
    href?: Href;
    onSelect?: () => void;
    destructive?: boolean;
};

type Row = { id: number };

type Props<T extends Row> = {
    data: Yuan.AdminCore.Data.PaginatedData<T>;
    state: Yuan.AdminCore.Data.DataTableStateData;
    columns: DataTableColumn<T>[];
    /** The module slug; keys the column choice in localStorage (`dt_cols:{slug}`). */
    slug: string;
    /** Accessible name of the table, e.g. "Daftar invoice". */
    label: string;
    /** Noun after the count in "Menampilkan 1–10 dari 248 invoice". */
    itemLabel: string;
    /** Names a row for screen readers and the delete confirm. */
    rowLabel: (row: T) => string;
    searchPlaceholder?: string;
    /** Row click opens this (pass it only when `view` is allowed). */
    rowHref?: (row: T) => Href;
    /** Row click runs this instead of a visit (e.g. opens a drawer); give keyboard users the same through `rowActions`. */
    onRowClick?: (row: T) => void;
    /** The `⋯` menu; return only the permitted actions. */
    rowActions?: (row: T) => RowAction[];
    /**
     * Shows the checkbox column and the bulk bar (pass it only when `delete`
     * is allowed). Call `onFinish` when the request ends: the confirm button
     * spins until then.
     */
    bulkDelete?: (ids: number[], onFinish: () => void) => void;
    /** Shown when the list is empty and nothing is filtered. */
    empty: EmptyStateProps;
    /** The module's download route (pass it only when `download` is allowed); sends the current search, filters and sort. */
    download?: Href;
    /** Props a list visit reloads (partial reload); every prop when omitted. */
    only?: string[];
};

const INTERACTIVE =
    'a, button, input, [role="checkbox"], [role="menu"], [role="menuitem"]';

/**
 * Server-side table (frontend-behavior §5, ui-design §3.1): state in the URL,
 * search, filter chips, sort, column choice, page-scoped selection with a bulk
 * delete, row menu, skeleton rows while loading, empty / no-results / error.
 */
export function DataTable<T extends Row>({
    data,
    state,
    columns,
    slug,
    label,
    itemLabel,
    rowLabel,
    searchPlaceholder,
    rowHref,
    onRowClick,
    rowActions,
    bulkDelete,
    empty,
    download,
    only,
}: Props<T>) {
    const t = useT();
    const deleting = useAction();
    const downloading = useAction();
    const query = useTableQuery(state, data.meta.current_page, only);
    const [hidden, setHidden] = useHiddenColumns(slug);
    // Selection belongs to the page it was made on: new data (paging, filtering,
    // a delete) starts with nothing selected.
    const [selection, setSelection] = useState<{
        data: typeof data;
        ids: number[];
    }>({ data, ids: [] });
    const selected = selection.data === data ? selection.ids : [];
    const [confirming, setConfirming] = useState(false);

    const visible = columns.filter((column) => !hidden.includes(column.key));
    const rows = data.items;
    const allSelected = rows.length > 0 && selected.length === rows.length;
    const select = (ids: number[]) => setSelection({ data, ids });

    const openRow = (event: MouseEvent<HTMLTableRowElement>, row: T) => {
        if ((event.target as HTMLElement).closest(INTERACTIVE)) {
            return;
        }

        if (onRowClick) {
            onRowClick(row);
        } else if (rowHref) {
            router.visit(rowHref(row));
        }
    };

    const showEmpty = !query.failed && !query.loading && rows.length === 0;

    return (
        <>
            <DataTableToolbar
                state={state}
                query={query}
                searchPlaceholder={searchPlaceholder}
                columns={columns.filter((column) => column.hideable !== false)}
                hidden={hidden}
                onHiddenChange={setHidden}
                onDownload={
                    download
                        ? () =>
                              downloading.run((done) =>
                                  query.download(
                                      typeof download === 'string'
                                          ? download
                                          : download.url,
                                      done,
                                  ),
                              )
                        : undefined
                }
                downloading={downloading.pending}
            />

            <section
                aria-label={label}
                className="flex min-h-0 flex-col rounded bg-card text-card-foreground"
            >
                {bulkDelete && selected.length > 0 && (
                    <div
                        role="status"
                        className="flex items-center gap-3 rounded-t bg-muted px-3 py-2 text-table"
                    >
                        <strong className="font-semibold tabular-nums">
                            {t('table.selected', { count: selected.length })}
                        </strong>
                        <Button
                            variant="outline"
                            size="compact"
                            className="border-destructive bg-transparent font-semibold text-destructive hover:bg-destructive/10 hover:text-destructive"
                            onClick={() => setConfirming(true)}
                        >
                            <Trash2 />
                            {t('table.delete')}
                        </Button>
                        <div className="flex-1" />
                        <Button
                            variant="ghost"
                            size="compact"
                            onClick={() => select([])}
                        >
                            {t('table.clear_selection')}
                        </Button>
                    </div>
                )}

                {query.failed ? (
                    <div className="p-6">
                        <div
                            role="alert"
                            className="flex items-start gap-2.5 rounded bg-destructive/12 p-3 text-table"
                        >
                            <AlertCircle className="mt-0.5 size-4 shrink-0 text-destructive" />
                            <div className="flex flex-1 flex-col gap-0.5">
                                <strong className="font-semibold text-destructive">
                                    {t('table.error_title')}
                                </strong>
                                <span>{t('table.error_text')}</span>
                            </div>
                            <Button
                                variant="outline"
                                size="compact"
                                className="bg-card font-semibold"
                                onClick={query.retry}
                            >
                                {t('table.retry')}
                            </Button>
                        </div>
                    </div>
                ) : showEmpty ? (
                    query.filtered ? (
                        <EmptyState
                            icon={SearchX}
                            title={t('table.empty_filtered_title')}
                            description={t('table.empty_filtered_text')}
                            action={
                                <Button
                                    variant="outline"
                                    className="bg-transparent font-semibold"
                                    onClick={query.reset}
                                >
                                    {t('table.reset_filters')}
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState {...empty} />
                    )
                ) : (
                    <div className="overflow-x-auto">
                        <table
                            className="w-full min-w-3xl border-collapse text-table tabular-nums"
                            aria-rowcount={data.meta.total}
                        >
                            <thead>
                                <tr className="h-10 border-b border-rule">
                                    {bulkDelete && (
                                        <th scope="col" className="w-10 pl-3">
                                            <Checkbox
                                                aria-label={t(
                                                    'table.select_all',
                                                )}
                                                checked={
                                                    allSelected
                                                        ? true
                                                        : selected.length > 0
                                                          ? 'indeterminate'
                                                          : false
                                                }
                                                onCheckedChange={() =>
                                                    select(
                                                        allSelected
                                                            ? []
                                                            : rows.map(
                                                                  (row) =>
                                                                      row.id,
                                                              ),
                                                    )
                                                }
                                            />
                                        </th>
                                    )}
                                    {visible.map((column) => (
                                        <HeaderCell
                                            key={column.key}
                                            column={column}
                                            sort={
                                                state.sortable.includes(
                                                    column.key,
                                                )
                                                    ? state.sort
                                                    : undefined
                                            }
                                            onSort={() =>
                                                query.toggleSort(column.key)
                                            }
                                        />
                                    ))}
                                    {rowActions && (
                                        <th scope="col" className="w-12">
                                            <span className="sr-only">
                                                {t('table.actions')}
                                            </span>
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {query.loading
                                    ? skeletonRows(
                                          Math.min(state.per_page, 10),
                                      ).map((key) => (
                                          <tr
                                              key={key}
                                              aria-hidden="true"
                                              className="h-row border-b border-border"
                                          >
                                              {bulkDelete && (
                                                  <td className="pl-3">
                                                      <div className="size-4 rounded bg-muted" />
                                                  </td>
                                              )}
                                              {visible.map((column, index) => (
                                                  <td
                                                      key={column.key}
                                                      className="px-3"
                                                  >
                                                      <div
                                                          className={cn(
                                                              'h-3 rounded bg-muted',
                                                              [
                                                                  'w-3/5',
                                                                  'w-1/2',
                                                                  'w-2/3',
                                                                  'w-11/20',
                                                              ][
                                                                  (key +
                                                                      index) %
                                                                      4
                                                              ],
                                                          )}
                                                      />
                                                  </td>
                                              ))}
                                              {rowActions && <td />}
                                          </tr>
                                      ))
                                    : rows.map((row) => {
                                          const checked = selected.includes(
                                              row.id,
                                          );

                                          return (
                                              <tr
                                                  key={row.id}
                                                  aria-selected={
                                                      bulkDelete
                                                          ? checked
                                                          : undefined
                                                  }
                                                  onClick={(event) =>
                                                      openRow(event, row)
                                                  }
                                                  className={cn(
                                                      'h-row border-b border-border hover:bg-muted has-[[aria-expanded=true]]:bg-muted',
                                                      checked && 'bg-muted/60',
                                                      (rowHref || onRowClick) &&
                                                          'cursor-pointer',
                                                  )}
                                              >
                                                  {bulkDelete && (
                                                      <td className="pl-3">
                                                          <Checkbox
                                                              aria-label={t(
                                                                  'table.select_row',
                                                                  {
                                                                      name: rowLabel(
                                                                          row,
                                                                      ),
                                                                  },
                                                              )}
                                                              checked={checked}
                                                              onCheckedChange={() =>
                                                                  select(
                                                                      checked
                                                                          ? selected.filter(
                                                                                (
                                                                                    id,
                                                                                ) =>
                                                                                    id !==
                                                                                    row.id,
                                                                            )
                                                                          : [
                                                                                ...selected,
                                                                                row.id,
                                                                            ],
                                                                  )
                                                              }
                                                          />
                                                      </td>
                                                  )}
                                                  {visible.map((column) => (
                                                      <td
                                                          key={column.key}
                                                          className={cn(
                                                              'max-w-80 truncate px-3 whitespace-nowrap',
                                                              column.align ===
                                                                  'right' &&
                                                                  'text-right',
                                                              column.className,
                                                          )}
                                                      >
                                                          {column.cell
                                                              ? column.cell(row)
                                                              : String(
                                                                    row[
                                                                        column
                                                                            .key
                                                                    ] ?? '',
                                                                )}
                                                      </td>
                                                  ))}
                                                  {rowActions && (
                                                      <td className="px-2 text-right">
                                                          <RowMenu
                                                              name={rowLabel(
                                                                  row,
                                                              )}
                                                              actions={rowActions(
                                                                  row,
                                                              )}
                                                          />
                                                      </td>
                                                  )}
                                              </tr>
                                          );
                                      })}
                            </tbody>
                        </table>
                    </div>
                )}

                {!query.failed && !showEmpty && (
                    <DataTablePagination
                        meta={data.meta}
                        state={state}
                        query={query}
                        itemLabel={itemLabel}
                    />
                )}
            </section>

            {bulkDelete && (
                <ConfirmDialog
                    open={confirming}
                    onOpenChange={setConfirming}
                    title={t('table.delete')}
                    itemName={t('dialog.delete_many', {
                        count: selected.length,
                    })}
                    loading={deleting.pending}
                    onConfirm={() =>
                        deleting.run((done) =>
                            bulkDelete(selected, () => {
                                done();
                                setConfirming(false);
                            }),
                        )
                    }
                />
            )}
        </>
    );
}

function HeaderCell<T>({
    column,
    sort,
    onSort,
}: {
    column: DataTableColumn<T>;
    /** undefined: the column isn't sortable. */
    sort: string | null | undefined;
    onSort: () => void;
}) {
    const base = cn(
        'px-3 text-label whitespace-nowrap uppercase',
        column.align === 'right' ? 'text-right' : 'text-left',
    );

    if (sort === undefined) {
        return (
            <th scope="col" className={cn(base, 'text-muted-foreground')}>
                {column.label}
            </th>
        );
    }

    const direction =
        sort === column.key
            ? 'ascending'
            : sort === `-${column.key}`
              ? 'descending'
              : 'none';
    const Icon =
        direction === 'ascending'
            ? ArrowUp
            : direction === 'descending'
              ? ArrowDown
              : ArrowUpDown;

    return (
        <th
            scope="col"
            aria-sort={direction}
            className={cn(
                base,
                direction === 'none'
                    ? 'text-muted-foreground'
                    : 'text-foreground',
            )}
        >
            <button
                type="button"
                onClick={onSort}
                className={cn(
                    'inline-flex items-center gap-1 uppercase hover:text-foreground',
                    column.align === 'right' && 'flex-row-reverse',
                )}
            >
                {column.label}
                <Icon aria-hidden="true" className="size-3.5" />
            </button>
        </th>
    );
}

function RowMenu({ name, actions }: { name: string; actions: RowAction[] }) {
    const t = useT();

    if (actions.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 aria-expanded:bg-accent"
                    aria-label={t('table.actions_for', { name })}
                >
                    <MoreHorizontal />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-42">
                {actions.map((action, index) => {
                    const Icon = action.icon;
                    const content = (
                        <>
                            {Icon && <Icon />}
                            {action.label}
                        </>
                    );

                    return (
                        <Fragment key={action.label}>
                            {action.destructive && index > 0 && (
                                <DropdownMenuSeparator />
                            )}
                            <DropdownMenuItem
                                variant={
                                    action.destructive
                                        ? 'destructive'
                                        : 'default'
                                }
                                onSelect={action.onSelect}
                                asChild={Boolean(action.href)}
                            >
                                {action.href ? (
                                    <Link href={action.href}>{content}</Link>
                                ) : (
                                    content
                                )}
                            </DropdownMenuItem>
                        </Fragment>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function skeletonRows(count: number): number[] {
    return Array.from({ length: count }, (_, index) => index);
}

/** Hidden column keys per table, in localStorage `dt_cols:{slug}`; storage may be unavailable. */
function useHiddenColumns(slug: string): [string[], (keys: string[]) => void] {
    const storageKey = `dt_cols:${slug}`;
    const [hidden, setHidden] = useState<string[]>(() => {
        try {
            const stored: unknown = JSON.parse(
                window.localStorage.getItem(storageKey) ?? '[]',
            );

            return Array.isArray(stored)
                ? stored.filter((key): key is string => typeof key === 'string')
                : [];
        } catch {
            return [];
        }
    });

    return [
        hidden,
        (keys) => {
            setHidden(keys);

            try {
                window.localStorage.setItem(storageKey, JSON.stringify(keys));
            } catch {
                // Not persisted; the choice still applies until the page is left.
            }
        },
    ];
}
