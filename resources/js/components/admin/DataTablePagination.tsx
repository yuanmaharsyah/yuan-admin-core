import { ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import type { useTableQuery } from '../../hooks/useTableQuery';
import { formatNumber } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';

type Props = {
    meta: Yuan.AdminCore.Data.PaginationMetaData;
    state: Yuan.AdminCore.Data.DataTableStateData;
    query: ReturnType<typeof useTableQuery>;
    itemLabel: string;
};

/** "Menampilkan 1–10 dari 248 …", rows per page, and page buttons with gaps. */
export function DataTablePagination({ meta, state, query, itemLabel }: Props) {
    const t = useT();
    const from =
        meta.total === 0 ? 0 : (meta.current_page - 1) * meta.per_page + 1;
    const to = Math.min(meta.current_page * meta.per_page, meta.total);
    const range = {
        from: formatNumber(from),
        to: formatNumber(to),
        total: formatNumber(meta.total),
        item: itemLabel,
    };

    return (
        <div
            className={cn(
                'flex flex-wrap items-center gap-x-4 gap-y-3 px-3 py-2.5 text-table text-muted-foreground tabular-nums',
                query.loading && 'opacity-50',
            )}
        >
            <span className="flex-1 whitespace-nowrap">
                {query.loading ? (
                    t('table.loading')
                ) : (
                    <>
                        <span className="max-sm:hidden">
                            {t('table.range', range)}
                        </span>
                        <span className="sm:hidden">
                            {t('table.range_short', range)}
                        </span>
                    </>
                )}
            </span>

            <div className="flex items-center gap-2 max-sm:hidden">
                {t('table.per_page')}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            size="compact"
                            aria-label={t('table.per_page_value', {
                                count: state.per_page,
                            })}
                            className="gap-1.5 border-border bg-card px-2 text-table text-foreground"
                        >
                            {state.per_page}
                            <ChevronDown />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="min-w-20">
                        <DropdownMenuRadioGroup
                            value={String(state.per_page)}
                            onValueChange={(value) =>
                                query.setPerPage(Number(value))
                            }
                        >
                            {state.per_page_options.map((option) => (
                                <DropdownMenuRadioItem
                                    key={option}
                                    value={String(option)}
                                >
                                    {option}
                                </DropdownMenuRadioItem>
                            ))}
                        </DropdownMenuRadioGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <nav
                aria-label={t('table.pages')}
                className="flex items-center gap-1"
            >
                <PageButton
                    label={t('table.previous')}
                    disabled={meta.current_page <= 1}
                    onClick={() => query.setPage(meta.current_page - 1)}
                >
                    <ChevronLeft className="size-4" />
                </PageButton>
                {pageWindow(meta.current_page, meta.last_page).map(
                    (page, index) =>
                        page === null ? (
                            <span
                                key={`gap-${index}`}
                                aria-label={t('table.more_pages')}
                                className="flex h-control-compact min-w-7.5 items-center justify-center text-foreground max-sm:hidden"
                            >
                                …
                            </span>
                        ) : (
                            <PageButton
                                key={page}
                                label={t('table.page', { page })}
                                current={page === meta.current_page}
                                onClick={() => query.setPage(page)}
                                className="max-sm:hidden"
                            >
                                {page}
                            </PageButton>
                        ),
                )}
                <PageButton
                    label={t('table.next')}
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => query.setPage(meta.current_page + 1)}
                >
                    <ChevronRight className="size-4" />
                </PageButton>
            </nav>
        </div>
    );
}

function PageButton({
    label,
    current = false,
    disabled = false,
    onClick,
    className,
    children,
}: {
    label: string;
    current?: boolean;
    disabled?: boolean;
    onClick: () => void;
    className?: string;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            aria-current={current ? 'page' : undefined}
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'flex h-control-compact min-w-7.5 items-center justify-center rounded border px-1.5 text-table font-medium disabled:opacity-45',
                current
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border text-foreground hover:bg-accent',
                className,
            )}
        >
            {children}
        </button>
    );
}

/** 1 … around the current page … last, as page numbers and gaps (null). */
function pageWindow(current: number, last: number): (number | null)[] {
    const pages = [...new Set([1, current - 1, current, current + 1, last])]
        .filter((page) => page >= 1 && page <= last)
        .sort((a, b) => a - b);

    return pages.flatMap((page, index) =>
        index > 0 && page - pages[index - 1] > 1 ? [null, page] : [page],
    );
}
