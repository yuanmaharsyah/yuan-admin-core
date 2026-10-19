import { Columns3, PlusCircle, Search, X } from 'lucide-react';
import type { useTableQuery } from '../../hooks/useTableQuery';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { Input } from '../ui/input';

type Props = {
    state: Yuan.AdminCore.Data.DataTableStateData;
    query: ReturnType<typeof useTableQuery>;
    searchPlaceholder?: string;
    columns: { key: string; label: string }[];
    hidden: string[];
    onHiddenChange: (keys: string[]) => void;
};

/** Search, one chip per filter (dashed when unset), Reset, and the column menu. */
export function DataTableToolbar({
    state,
    query,
    searchPlaceholder,
    columns,
    hidden,
    onHiddenChange,
}: Props) {
    const t = useT();

    return (
        <div className="flex flex-wrap items-center gap-2">
            {state.searchable && (
                <div className="relative flex max-w-80 flex-[1_1_15rem]">
                    <Search
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        type="search"
                        aria-label={t('table.search_label')}
                        placeholder={searchPlaceholder ?? t('table.search')}
                        value={query.search}
                        onChange={(event) =>
                            query.setSearch(event.target.value)
                        }
                        className="bg-card pl-8.5"
                    />
                </div>
            )}

            {state.filters.map((filter) => {
                const value = state.filter[filter.key];
                const selected = filter.options.find(
                    (option) => option.value === value,
                );

                return (
                    <div key={filter.key} className="flex">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    className={cn(
                                        'gap-1.5 px-2.5 text-table',
                                        selected
                                            ? 'rounded-r-none border-r-0 border-primary bg-card'
                                            : 'border-dashed bg-transparent',
                                    )}
                                >
                                    {!selected && <PlusCircle />}
                                    {selected
                                        ? `${filter.label}: ${selected.label}`
                                        : filter.label}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                <DropdownMenuRadioGroup
                                    value={value ?? ''}
                                    onValueChange={(next) =>
                                        query.setFilter(filter.key, next)
                                    }
                                >
                                    {filter.options.map((option) => (
                                        <DropdownMenuRadioItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </DropdownMenuRadioItem>
                                    ))}
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        {selected && (
                            <Button
                                variant="outline"
                                size="icon"
                                aria-label={t('table.clear_filter', {
                                    label: filter.label,
                                })}
                                className="rounded-l-none border-primary bg-card"
                                onClick={() =>
                                    query.setFilter(filter.key, null)
                                }
                            >
                                <X />
                            </Button>
                        )}
                    </div>
                );
            })}

            {query.filtered && (
                <Button
                    variant="link"
                    className="px-2 text-table text-foreground underline underline-offset-3"
                    onClick={query.reset}
                >
                    {t('table.reset')}
                </Button>
            )}

            <div className="flex-1" />

            {columns.length > 0 && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            aria-label={t('table.columns_label')}
                            className="gap-1.5 border-border bg-card px-2.5 text-table"
                        >
                            <Columns3 />
                            <span className="max-sm:sr-only">
                                {t('table.columns')}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {columns.map((column) => (
                            <DropdownMenuCheckboxItem
                                key={column.key}
                                checked={!hidden.includes(column.key)}
                                // A column can't be hidden if it is the last one shown.
                                disabled={
                                    !hidden.includes(column.key) &&
                                    hidden.length >= columns.length - 1
                                }
                                onSelect={(event) => event.preventDefault()}
                                onCheckedChange={(shown) =>
                                    onHiddenChange(
                                        shown
                                            ? hidden.filter(
                                                  (key) => key !== column.key,
                                              )
                                            : [...hidden, column.key],
                                    )
                                }
                            >
                                {column.label}
                            </DropdownMenuCheckboxItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    );
}
