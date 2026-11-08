import {
    closestCorners,
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type {
    DragEndEvent,
    DragOverEvent,
    DragStartEvent,
    KeyboardCoordinateGetter,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { MoreHorizontal } from 'lucide-react';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { dragStyle } from './DataTableParts';

export type KanbanColumn = { key: string; label: string; ids: number[] };

/**
 * Keyboard moves (Space, arrows, Space): Left / Right jump to the top of the
 * neighbouring column (the stock sortable helper stays inside one list),
 * Up / Down move within the column.
 */
const boardKeyboardCoordinates: KeyboardCoordinateGetter = (event, args) => {
    const { droppableRects, collisionRect, droppableContainers } = args.context;

    if (
        (event.code !== 'ArrowLeft' && event.code !== 'ArrowRight') ||
        !collisionRect
    ) {
        return sortableKeyboardCoordinates(event, args);
    }

    event.preventDefault();
    const columns = droppableContainers
        .getEnabled()
        .filter((container) => String(container.id).startsWith('column:'))
        .flatMap((container) => {
            const rect = droppableRects.get(container.id);

            return rect ? [rect] : [];
        })
        .filter((rect) =>
            event.code === 'ArrowRight'
                ? rect.left > collisionRect.left + 1
                : rect.left < collisionRect.left - 1,
        )
        .sort((a, b) =>
            event.code === 'ArrowRight' ? a.left - b.left : b.left - a.left,
        );
    const target = columns[0];

    return target ? { x: target.left + 8, y: target.top + 8 } : undefined;
};

type Props<T> = {
    /** Accessible name of the board, e.g. "Papan tugas". */
    label: string;
    columns: KanbanColumn[];
    items: Record<number, T>;
    renderCard: (item: T) => ReactNode;
    /** Names a card for screen readers. */
    cardLabel: (item: T) => string;
    /**
     * Called after a move with every column's ids in order; send them to the
     * module's `PATCH` route and call `done(false)` to put the cards back.
     * Without it the board is read-only.
     */
    onMove?: (
        columns: Record<string, number[]>,
        done: (saved: boolean) => void,
    ) => void;
};

/**
 * A kanban board (restyle 03, demo "Kanban"): one column per status. Cards
 * move by dragging (within and between columns), with the keyboard inside a
 * column (Space, arrows, Space), and through each card's "Pindahkan ke" menu
 * to another column (the keyboard and single-pointer way, WCAG 2.5.7). The new order goes to `onMove`; the board keeps it unless the save
 * fails.
 */
export function KanbanBoard<T>({
    label,
    columns,
    items,
    renderCard,
    cardLabel,
    onMove,
}: Props<T>) {
    const t = useT();
    const [board, setBoard] = useState<{
        source: KanbanColumn[];
        columns: KanbanColumn[];
    }>({ source: columns, columns });
    // New props (a reload) replace the local order.
    const current = board.source === columns ? board.columns : columns;
    const [before, setBefore] = useState<KanbanColumn[] | null>(null);
    const [dragging, setDragging] = useState<number | null>(null);
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: boardKeyboardCoordinates,
        }),
    );

    const update = (next: KanbanColumn[]) =>
        setBoard({ source: columns, columns: next });
    const columnOf = (id: string | number): KanbanColumn | undefined =>
        current.find(
            (column) =>
                `column:${column.key}` === id ||
                column.ids.includes(Number(String(id).replace('card:', ''))),
        );

    const onDragStart = ({ active }: DragStartEvent) => {
        setBefore(current);
        setDragging(Number(String(active.id).replace('card:', '')));
    };

    // Across columns: move the card into the column it hovers, at the hovered card's place.
    const onDragOver = ({ active, over }: DragOverEvent) => {
        const from = columnOf(active.id);
        const to = over ? columnOf(over.id) : undefined;

        if (!from || !to || from.key === to.key) {
            return;
        }

        const id = Number(String(active.id).replace('card:', ''));
        const at = String(over?.id).startsWith('card:')
            ? to.ids.indexOf(Number(String(over?.id).replace('card:', '')))
            : to.ids.length;

        update(
            current.map((column) =>
                column.key === from.key
                    ? {
                          ...column,
                          ids: column.ids.filter((other) => other !== id),
                      }
                    : column.key === to.key
                      ? {
                            ...column,
                            ids: [
                                ...column.ids.slice(0, Math.max(at, 0)),
                                id,
                                ...column.ids.slice(Math.max(at, 0)),
                            ],
                        }
                      : column,
            ),
        );
    };

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        setDragging(null);
        const column = columnOf(active.id);
        let next = current;

        if (
            column &&
            over &&
            String(over.id).startsWith('card:') &&
            active.id !== over.id
        ) {
            const ids = column.ids.map((id) => `card:${id}`);
            next = current.map((other) =>
                other.key === column.key
                    ? {
                          ...other,
                          ids: arrayMove(
                              ids,
                              ids.indexOf(String(active.id)),
                              ids.indexOf(String(over.id)),
                          ).map((id) => Number(id.slice(5))),
                      }
                    : other,
            );
            update(next);
        }

        const previous = before;
        setBefore(null);

        if (previous && JSON.stringify(previous) !== JSON.stringify(next)) {
            onMove?.(
                Object.fromEntries(next.map((other) => [other.key, other.ids])),
                (saved) => !saved && update(previous),
            );
        }
    };

    // The menu's move: the card goes to the end of the chosen column.
    const send = (id: number, target: string) => {
        const previous = current;
        const next = current.map((column) => ({
            ...column,
            ids:
                column.key === target
                    ? [...column.ids.filter((other) => other !== id), id]
                    : column.ids.filter((other) => other !== id),
        }));
        update(next);
        onMove?.(
            Object.fromEntries(next.map((column) => [column.key, column.ids])),
            (saved) => !saved && update(previous),
        );
    };

    const dragged = dragging !== null ? items[dragging] : undefined;

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={onDragStart}
            onDragOver={onDragOver}
            onDragEnd={onDragEnd}
            onDragCancel={() => {
                setDragging(null);

                if (before) {
                    update(before);
                }
            }}
        >
            <div
                role="region"
                aria-label={label}
                className="grid auto-cols-[minmax(16rem,1fr)] grid-flow-col gap-4 overflow-x-auto pb-2"
            >
                {current.map((column) => (
                    <BoardColumn key={column.key} column={column}>
                        <SortableContext
                            items={column.ids.map((id) => `card:${id}`)}
                            strategy={verticalListSortingStrategy}
                            disabled={!onMove}
                        >
                            {column.ids.map((id) => {
                                const item = items[id];

                                return item === undefined ? null : (
                                    <Card
                                        key={id}
                                        id={id}
                                        label={t('board.move_card', {
                                            name: cardLabel(item),
                                        })}
                                        movable={Boolean(onMove)}
                                        menuLabel={t('board.send_card', {
                                            name: cardLabel(item),
                                        })}
                                        targets={current.filter(
                                            (other) => other.key !== column.key,
                                        )}
                                        onSend={(target) => send(id, target)}
                                    >
                                        {renderCard(item)}
                                    </Card>
                                );
                            })}
                        </SortableContext>
                    </BoardColumn>
                ))}
            </div>
            <DragOverlay>
                {dragged !== undefined && (
                    <div className="rotate-2 rounded-xl border border-border bg-card p-4 shadow-popover">
                        {renderCard(dragged)}
                    </div>
                )}
            </DragOverlay>
        </DndContext>
    );
}

function BoardColumn({
    column,
    children,
}: {
    column: KanbanColumn;
    children: ReactNode;
}) {
    const { setNodeRef, isOver } = useDroppable({ id: `column:${column.key}` });

    return (
        <section
            aria-label={column.label}
            className="flex min-h-80 flex-col gap-3 rounded-xl bg-muted p-3"
        >
            <header className="flex items-center justify-between px-1">
                <h2 className="m-0 text-body font-semibold">{column.label}</h2>
                <span className="rounded-full bg-background px-2 text-xs font-medium tabular-nums">
                    {column.ids.length}
                </span>
            </header>
            <div
                ref={setNodeRef}
                className={cn(
                    'flex flex-1 flex-col gap-3 rounded-lg transition-colors duration-150',
                    isOver && 'bg-accent',
                )}
            >
                {children}
            </div>
        </section>
    );
}

function Card({
    id,
    label,
    movable,
    menuLabel,
    targets,
    onSend,
    children,
}: {
    id: number;
    label: string;
    movable: boolean;
    /** "Pindahkan … ke": the menu that moves the card without dragging. */
    menuLabel: string;
    /** The other columns. */
    targets: KanbanColumn[];
    onSend: (column: string) => void;
    children: ReactNode;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: `card:${id}`, disabled: !movable });

    return (
        <div
            ref={setNodeRef}
            style={dragStyle(transform, transition)}
            className={cn('relative', isDragging && 'opacity-40')}
        >
            <article
                {...(movable ? attributes : {})}
                {...(movable ? listeners : {})}
                aria-label={movable ? label : undefined}
                className={cn(
                    'rounded-xl border border-border bg-card p-4 transition-shadow duration-150',
                    movable &&
                        'cursor-grab touch-none pr-12 hover:shadow-popover active:cursor-grabbing',
                )}
            >
                {children}
            </article>
            {movable && (
                <DropdownMenu>
                    <DropdownMenuTrigger
                        aria-label={menuLabel}
                        className="absolute top-3 right-3 flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors duration-150 hover:bg-accent hover:text-foreground"
                    >
                        <MoreHorizontal aria-hidden="true" className="size-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        {targets.map((column) => (
                            <DropdownMenuItem
                                key={column.key}
                                onSelect={() => onSend(column.key)}
                            >
                                {column.label}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    );
}
