import { Check, ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '../../lib/utils';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';

export type FilterField = {
    key: string;
    /** Accessible name of the menu, e.g. "User". */
    label: string;
    /** The choice that clears the filter, e.g. "Semua user". */
    allLabel: string;
    options: Yuan.AdminCore.Data.OptionData[];
};

type Props = {
    label: string;
    fields: FilterField[];
    value: Record<string, string | null>;
    /** Writes the URL state; null clears the filter. */
    onChange: (key: string, value: string | null) => void;
    /** Controls placed first, e.g. a RangeFilter. */
    children?: ReactNode;
};

/**
 * The page-level filters (frontend-behavior §8.3): one menu button per field,
 * outlined in ink while it filters. They drive every section of the page.
 */
export function FilterBar({ label, fields, value, onChange, children }: Props) {
    return (
        <div role="group" aria-label={label} className="flex flex-wrap gap-2">
            {children}
            {fields.map((field) => {
                const current = value[field.key] ?? null;
                const selected = field.options.find(
                    (option) => option.value === current,
                );

                return (
                    <DropdownMenu key={field.key}>
                        <DropdownMenuTrigger
                            aria-label={`${field.label}: ${selected?.label ?? field.allLabel}`}
                            className={cn(
                                'flex h-control items-center gap-2 rounded border bg-card px-2.5 text-table font-medium whitespace-nowrap',
                                current ? 'border-primary' : 'border-border',
                            )}
                        >
                            {selected?.label ?? field.allLabel}
                            <ChevronDown
                                aria-hidden="true"
                                className="size-3.5 text-muted-foreground"
                            />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            className="max-h-72 min-w-56 overflow-y-auto"
                        >
                            {[
                                { value: null, label: field.allLabel },
                                ...field.options,
                            ].map((option) => (
                                <DropdownMenuItem
                                    key={option.value ?? ''}
                                    onSelect={() =>
                                        onChange(field.key, option.value)
                                    }
                                >
                                    <span
                                        aria-hidden="true"
                                        className="flex w-4 justify-center"
                                    >
                                        {option.value === current && (
                                            <Check className="size-3.5" />
                                        )}
                                    </span>
                                    {option.label}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            })}
        </div>
    );
}
