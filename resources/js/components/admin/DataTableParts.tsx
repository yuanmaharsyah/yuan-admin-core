import type {
    DraggableAttributes,
    DraggableSyntheticListeners,
} from '@dnd-kit/core';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronRight, GripVertical } from 'lucide-react';
import type { CSSProperties, KeyboardEvent, ReactNode } from 'react';
import { useState } from 'react';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Spinner } from '../ui/spinner';

/**
 * Inline editing of one column (DataTable `columns[].edit`): the cell becomes
 * a field on click or Enter; Enter or leaving it saves through `save`, Esc
 * cancels. `save` sends the module's own update request and calls `done(true)`
 * when it went through, `done(false)` otherwise (the cell keeps the old value).
 */
export type ColumnEdit<T> = {
    type: 'text' | 'number' | 'select';
    /** For `select`. */
    options?: Yuan.AdminCore.Data.OptionData[];
    /** The value the field starts with. */
    value: (row: T) => string;
    save: (row: T, value: string, done: (saved: boolean) => void) => void;
};

/**
 * dnd-kit's drag position. The one inline style the panel uses: a dragged
 * element's offset changes every frame and no class can express it.
 */
export function dragStyle(
    transform: ReturnType<typeof useSortable>['transform'],
    transition: string | undefined,
): CSSProperties {
    return { transform: CSS.Translate.toString(transform), transition };
}

/** The grip a row or header is dragged by (mouse, touch, or Space + arrows). */
export function DragHandle({
    label,
    attributes,
    listeners,
    className,
}: {
    label: string;
    attributes: DraggableAttributes;
    listeners: DraggableSyntheticListeners;
    className?: string;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            {...attributes}
            {...listeners}
            className={cn(
                'inline-flex size-7 cursor-grab touch-none items-center justify-center rounded-md text-muted-foreground transition-colors duration-150 hover:bg-accent hover:text-foreground active:cursor-grabbing',
                className,
            )}
        >
            <GripVertical aria-hidden="true" className="size-4" />
        </button>
    );
}

/** The chevron that opens a row's details panel. */
export function ExpandButton({
    open,
    label,
    controls,
    onToggle,
}: {
    open: boolean;
    label: string;
    controls: string;
    onToggle: () => void;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            aria-expanded={open}
            aria-controls={controls}
            onClick={onToggle}
            className="inline-flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors duration-150 hover:bg-accent hover:text-foreground"
        >
            <ChevronRight
                aria-hidden="true"
                className={cn(
                    'size-4 transition-transform duration-200',
                    open && 'rotate-90',
                )}
            />
        </button>
    );
}

/** A row's details below it (demo "More Details"): a muted card that slides in. */
export function ExpandedPanel({
    id,
    colSpan,
    children,
}: {
    id: string;
    colSpan: number;
    children: ReactNode;
}) {
    return (
        <tr className="border-b border-border last:border-0">
            <td id={id} colSpan={colSpan} className="p-0">
                <div className="m-4 animate-in rounded-xl bg-muted p-6 duration-200 fade-in slide-in-from-top-2">
                    {children}
                </div>
            </td>
        </tr>
    );
}

/** One editable cell: the value, or its field while editing. */
export function EditableCell<T>({
    row,
    edit,
    label,
    children,
}: {
    row: T;
    edit: ColumnEdit<T>;
    /** The column label, for the field's name. */
    label: string;
    children: ReactNode;
}) {
    const t = useT();
    const [draft, setDraft] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const save = () => {
        if (draft === null || draft === edit.value(row)) {
            setDraft(null);

            return;
        }

        setSaving(true);
        edit.save(row, draft, () => {
            setSaving(false);
            setDraft(null);
        });
    };

    const keys = (event: KeyboardEvent) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            save();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setDraft(null);
        }
    };

    if (draft === null) {
        return (
            <button
                type="button"
                aria-label={t('table.edit_cell', { name: label })}
                onClick={() => setDraft(edit.value(row))}
                className="-mx-2 flex min-h-8 w-full items-center rounded-md px-2 text-left transition-colors duration-150 hover:bg-accent"
            >
                {children}
            </button>
        );
    }

    const field =
        'h-8 w-full rounded-md border border-input bg-background px-2 text-table';

    return (
        <span className="flex items-center gap-2">
            {edit.type === 'select' ? (
                <select
                    aria-label={label}
                    autoFocus
                    disabled={saving}
                    value={draft}
                    onChange={(event) => setDraft(event.target.value)}
                    onBlur={save}
                    onKeyDown={keys}
                    className={field}
                >
                    {(edit.options ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ) : (
                <input
                    aria-label={label}
                    autoFocus
                    disabled={saving}
                    inputMode={edit.type === 'number' ? 'decimal' : undefined}
                    value={draft}
                    onChange={(event) => setDraft(event.target.value)}
                    onBlur={save}
                    onKeyDown={keys}
                    className={field}
                />
            )}
            {saving && <Spinner aria-hidden="true" role={undefined} />}
        </span>
    );
}

/** The table's column order per user, in localStorage `dt_order:{slug}`; storage may be unavailable. */
export function useColumnOrder(
    slug: string,
): [string[], (keys: string[]) => void] {
    const storageKey = `dt_order:${slug}`;
    const [order, setOrder] = useState<string[]>(() => {
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
        order,
        (keys) => {
            setOrder(keys);

            try {
                window.localStorage.setItem(storageKey, JSON.stringify(keys));
            } catch {
                // Not persisted; the order still applies until the page is left.
            }
        },
    ];
}

/** `columns` in the stored order; columns the order doesn't know keep their place at the end. */
export function inOrder<C extends { key: string }>(
    columns: C[],
    order: string[],
): C[] {
    const rank = (key: string) => {
        const at = order.indexOf(key);

        return at === -1
            ? order.length + columns.findIndex((column) => column.key === key)
            : at;
    };

    return [...columns].sort((a, b) => rank(a.key) - rank(b.key));
}
