import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
    arrayMove,
    horizontalListSortingStrategy,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
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
import { Fragment, useId, useState } from 'react';
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
import type { ColumnEdit } from './DataTableParts';
import {
    DragHandle,
    dragStyle,
    EditableCell,
    ExpandButton,
    ExpandedPanel,
    inOrder,
    useColumnOrder,
} from './DataTableParts';
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
    /** Inline editing of this column's cells (see `ColumnEdit`). */
    edit?: ColumnEdit<T>;
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
    /** A details panel under a row, opened by a chevron (demo "Expanding table"). */
    expandable?: (row: T) => ReactNode;
    /** Headers can be dragged into another order (mouse or keyboard), kept per user (`dt_order:{slug}`). */
    reorderColumns?: boolean;
    /**
     * Rows can be dragged into another order (a model with a position): called
     * with the page's ids in the new order; call `done(false)` to put them back.
     */
    onReorder?: (ids: number[], done: (saved: boolean) => void) => void;
    /** The header stays visible while the rows scroll inside the table box. */
    stickyHeader?: boolean;
    /** The first column stays visible while the table scrolls sideways. */
    stickyFirstColumn?: boolean;
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
    expandable,
    reorderColumns = false,
    onReorder,
    stickyHeader = false,
    stickyFirstColumn = false,
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

    const [order, setOrder] = useColumnOrder(slug);
    const [expanded, setExpanded] = useState<number[]>([]);
    // A drag's new row order, until the next data arrives (or `done(false)`).
    const [rowOrder, setRowOrder] = useState<{
        data: typeof data;
        ids: number[];
    } | null>(null);
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const visible = inOrder(
        columns.filter((column) => !hidden.includes(column.key)),
        order,
    );
    const rows =
        rowOrder?.data === data
            ? rowOrder.ids.flatMap(
                  (id) => data.items.find((row) => row.id === id) ?? [],
              )
            : data.items;
    const span =
        visible.length +
        (bulkDelete ? 1 : 0) +
        (rowActions ? 1 : 0) +
        (expandable ? 1 : 0) +
        (onReorder ? 1 : 0);

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) {
            return;
        }

        const [from, to] = [String(active.id), String(over.id)];

        if (from.startsWith('col:')) {
            const keys = visible.map((column) => `col:${column.key}`);
            setOrder(
                arrayMove(keys, keys.indexOf(from), keys.indexOf(to)).map(
                    (key) => key.slice(4),
                ),
            );

            return;
        }

        const ids = rows.map((row) => `row:${row.id}`);
        const moved = arrayMove(ids, ids.indexOf(from), ids.indexOf(to)).map(
            (id) => Number(id.slice(4)),
        );
        setRowOrder({ data, ids: moved });
        onReorder?.(moved, (saved) => !saved && setRowOrder(null));
    };
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
            <div className="flex min-h-0 flex-col gap-6 rounded-xl border border-border bg-card p-4 text-card-foreground lg:p-6">
                <DataTableToolbar
                    state={state}
                    query={query}
                    searchPlaceholder={searchPlaceholder}
                    columns={columns.filter(
                        (column) => column.hideable !== false,
                    )}
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
                    className="flex min-h-0 flex-col overflow-hidden rounded-xl border border-border"
                >
                    {bulkDelete && selected.length > 0 && (
                        <div
                            role="status"
                            className="flex items-center gap-3 bg-muted px-3 py-2 text-table"
                        >
                            <strong className="font-semibold tabular-nums">
                                {t('table.selected', {
                                    count: selected.length,
                                })}
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
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={onDragEnd}
                        >
                            <div
                                className={cn(
                                    'overflow-x-auto',
                                    stickyHeader &&
                                        'max-h-[70dvh] overflow-y-auto',
                                )}
                            >
                                <table
                                    className="w-full min-w-3xl border-collapse text-table tabular-nums"
                                    aria-rowcount={data.meta.total}
                                >
                                    <thead
                                        className={cn(
                                            stickyHeader &&
                                                'sticky top-0 z-20 shadow-[inset_0_-1px_0] shadow-border [&_th]:bg-card',
                                        )}
                                    >
                                        <tr className="h-12 border-b border-border">
                                            {onReorder && (
                                                <th
                                                    scope="col"
                                                    className="w-10 pl-3"
                                                >
                                                    <span className="sr-only">
                                                        {t('table.order')}
                                                    </span>
                                                </th>
                                            )}
                                            {expandable && (
                                                <th
                                                    scope="col"
                                                    className="w-10 pl-3"
                                                >
                                                    <span className="sr-only">
                                                        {t('table.details')}
                                                    </span>
                                                </th>
                                            )}
                                            {bulkDelete && (
                                                <th
                                                    scope="col"
                                                    className="w-10 pl-3"
                                                >
                                                    <Checkbox
                                                        aria-label={t(
                                                            'table.select_all',
                                                        )}
                                                        checked={
                                                            allSelected
                                                                ? true
                                                                : selected.length >
                                                                    0
                                                                  ? 'indeterminate'
                                                                  : false
                                                        }
                                                        onCheckedChange={() =>
                                                            select(
                                                                allSelected
                                                                    ? []
                                                                    : rows.map(
                                                                          (
                                                                              row,
                                                                          ) =>
                                                                              row.id,
                                                                      ),
                                                            )
                                                        }
                                                    />
                                                </th>
                                            )}
                                            <SortableContext
                                                items={visible.map(
                                                    (column) =>
                                                        `col:${column.key}`,
                                                )}
                                                strategy={
                                                    horizontalListSortingStrategy
                                                }
                                                disabled={!reorderColumns}
                                            >
                                                {visible.map(
                                                    (column, index) => (
                                                        <HeaderCell
                                                            key={column.key}
                                                            column={column}
                                                            reorder={
                                                                reorderColumns
                                                            }
                                                            sticky={
                                                                stickyFirstColumn &&
                                                                index === 0
                                                            }
                                                            sort={
                                                                state.sortable.includes(
                                                                    column.key,
                                                                )
                                                                    ? state.sort
                                                                    : undefined
                                                            }
                                                            onSort={() =>
                                                                query.toggleSort(
                                                                    column.key,
                                                                )
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </SortableContext>
                                            {rowActions && (
                                                <th
                                                    scope="col"
                                                    className="w-12"
                                                >
                                                    <span className="sr-only">
                                                        {t('table.actions')}
                                                    </span>
                                                </th>
                                            )}
                                        </tr>
                                    </thead>
                                    {/* Data rows fade up one after another (admin-core.css `data-rows-in`); skeleton rows don't. */}
                                    <tbody
                                        data-rows-in={
                                            !query.loading || undefined
                                        }
                                    >
                                        {query.loading ? (
                                            skeletonRows(
                                                Math.min(state.per_page, 10),
                                            ).map((key) => (
                                                <tr
                                                    key={key}
                                                    aria-hidden="true"
                                                    className="h-14 border-b border-border last:border-0"
                                                >
                                                    {Array.from(
                                                        {
                                                            length:
                                                                span -
                                                                visible.length -
                                                                (rowActions
                                                                    ? 1
                                                                    : 0),
                                                        },
                                                        (_, index) => (
                                                            <td
                                                                key={`lead-${index}`}
                                                                className="pl-3"
                                                            >
                                                                <div className="size-4 rounded bg-muted" />
                                                            </td>
                                                        ),
                                                    )}
                                                    {visible.map(
                                                        (column, index) => (
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
                                                        ),
                                                    )}
                                                    {rowActions && <td />}
                                                </tr>
                                            ))
                                        ) : (
                                            <SortableContext
                                                items={rows.map(
                                                    (row) => `row:${row.id}`,
                                                )}
                                                strategy={
                                                    verticalListSortingStrategy
                                                }
                                                disabled={!onReorder}
                                            >
                                                {rows.map((row) => {
                                                    const checked =
                                                        selected.includes(
                                                            row.id,
                                                        );

                                                    return (
                                                        <BodyRow
                                                            key={row.id}
                                                            row={row}
                                                            columns={visible}
                                                            name={rowLabel(row)}
                                                            span={span}
                                                            reorder={Boolean(
                                                                onReorder,
                                                            )}
                                                            stickyFirstColumn={
                                                                stickyFirstColumn
                                                            }
                                                            expandable={
                                                                expandable
                                                            }
                                                            open={expanded.includes(
                                                                row.id,
                                                            )}
                                                            onToggle={() =>
                                                                setExpanded(
                                                                    expanded.includes(
                                                                        row.id,
                                                                    )
                                                                        ? expanded.filter(
                                                                              (
                                                                                  id,
                                                                              ) =>
                                                                                  id !==
                                                                                  row.id,
                                                                          )
                                                                        : [
                                                                              ...expanded,
                                                                              row.id,
                                                                          ],
                                                                )
                                                            }
                                                            checkbox={
                                                                bulkDelete
                                                                    ? {
                                                                          checked,
                                                                          onChange:
                                                                              () =>
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
                                                                                  ),
                                                                      }
                                                                    : null
                                                            }
                                                            actions={rowActions?.(
                                                                row,
                                                            )}
                                                            clickable={Boolean(
                                                                rowHref ||
                                                                onRowClick,
                                                            )}
                                                            onClick={(event) =>
                                                                openRow(
                                                                    event,
                                                                    row,
                                                                )
                                                            }
                                                        />
                                                    );
                                                })}
                                            </SortableContext>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </DndContext>
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
            </div>

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
    reorder,
    sticky,
}: {
    column: DataTableColumn<T>;
    /** undefined: the column isn't sortable. */
    sort: string | null | undefined;
    onSort: () => void;
    /** A grip lets the header be dragged into another place. */
    reorder: boolean;
    sticky: boolean;
}) {
    const t = useT();
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: `col:${column.key}`, disabled: !reorder });
    const base = cn(
        'group/th px-3 text-base font-semibold whitespace-nowrap',
        column.align === 'right' ? 'text-right' : 'text-left',
        sticky && 'sticky left-0 z-10 bg-card',
        isDragging && 'relative z-30 bg-muted',
    );
    const grip = reorder && (
        <DragHandle
            label={t('table.move_column', { name: column.label })}
            attributes={attributes}
            listeners={listeners}
            className="-ml-2 opacity-0 group-hover/th:opacity-100 focus-visible:opacity-100"
        />
    );

    if (sort === undefined) {
        return (
            <th
                ref={setNodeRef}
                style={reorder ? dragStyle(transform, transition) : undefined}
                scope="col"
                className={base}
            >
                <span
                    className={cn(
                        'inline-flex items-center gap-1',
                        column.align === 'right' && 'flex-row-reverse',
                    )}
                >
                    {grip}
                    {column.label}
                </span>
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
            ref={setNodeRef}
            style={reorder ? dragStyle(transform, transition) : undefined}
            scope="col"
            aria-sort={direction}
            className={cn(base, 'text-foreground')}
        >
            <span
                className={cn(
                    'inline-flex items-center gap-1',
                    column.align === 'right' && 'flex-row-reverse',
                )}
            >
                {grip}
                <button
                    type="button"
                    onClick={onSort}
                    className={cn(
                        'group inline-flex items-center gap-1.5',
                        column.align === 'right' && 'flex-row-reverse',
                    )}
                >
                    {column.label}
                    <Icon
                        aria-hidden="true"
                        className={cn(
                            'size-3.5 transition-opacity duration-150',
                            direction === 'none' &&
                                'text-muted-foreground opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100',
                        )}
                    />
                </button>
            </span>
        </th>
    );
}

/**
 * One data row: optional drag handle, expand chevron and checkbox, the cells
 * (inline-editable where the column says so), the row menu, and its details
 * panel below while open.
 */
function BodyRow<T extends Row>({
    row,
    columns,
    name,
    span,
    reorder,
    stickyFirstColumn,
    expandable,
    open,
    onToggle,
    checkbox,
    actions,
    clickable,
    onClick,
}: {
    row: T;
    columns: DataTableColumn<T>[];
    name: string;
    span: number;
    reorder: boolean;
    stickyFirstColumn: boolean;
    expandable?: (row: T) => ReactNode;
    open: boolean;
    onToggle: () => void;
    checkbox: { checked: boolean; onChange: () => void } | null;
    actions?: RowAction[];
    clickable: boolean;
    onClick: (event: MouseEvent<HTMLTableRowElement>) => void;
}) {
    const t = useT();
    const panelId = useId();
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: `row:${row.id}`, disabled: !reorder });

    return (
        <>
            <tr
                ref={setNodeRef}
                style={reorder ? dragStyle(transform, transition) : undefined}
                aria-selected={checkbox ? checkbox.checked : undefined}
                onClick={onClick}
                className={cn(
                    'group/row h-14 border-b border-border transition-colors duration-150 last:border-0 hover:bg-muted/50 has-[[aria-expanded=true]]:bg-muted/50',
                    checkbox?.checked && 'bg-muted/60',
                    clickable && 'cursor-pointer',
                    isDragging && 'relative z-10 bg-card shadow-popover',
                )}
            >
                {reorder && (
                    <td className="w-10 pl-2">
                        <DragHandle
                            label={t('table.move_row', { name })}
                            attributes={attributes}
                            listeners={listeners}
                        />
                    </td>
                )}
                {expandable && (
                    <td className="w-10 pl-2">
                        <ExpandButton
                            open={open}
                            label={t('table.toggle_details', { name })}
                            controls={panelId}
                            onToggle={onToggle}
                        />
                    </td>
                )}
                {checkbox && (
                    <td className="pl-3">
                        <Checkbox
                            aria-label={t('table.select_row', { name })}
                            checked={checkbox.checked}
                            onCheckedChange={checkbox.onChange}
                        />
                    </td>
                )}
                {columns.map((column, index) => {
                    const content = column.cell
                        ? column.cell(row)
                        : String(row[column.key] ?? '');

                    return (
                        <td
                            key={column.key}
                            className={cn(
                                'max-w-80 truncate px-3 whitespace-nowrap',
                                column.align === 'right' && 'text-right',
                                stickyFirstColumn &&
                                    index === 0 &&
                                    'sticky left-0 z-[1] bg-card group-hover/row:bg-muted',
                                column.className,
                            )}
                        >
                            {column.edit ? (
                                <EditableCell
                                    row={row}
                                    edit={column.edit}
                                    label={column.label}
                                >
                                    {content}
                                </EditableCell>
                            ) : (
                                content
                            )}
                        </td>
                    );
                })}
                {actions && (
                    <td className="px-2 text-right">
                        <RowMenu name={name} actions={actions} />
                    </td>
                )}
            </tr>
            {expandable && open && (
                <ExpandedPanel id={panelId} colSpan={span}>
                    {expandable(row)}
                </ExpandedPanel>
            )}
        </>
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
