<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @template T of Data
 */
#[TypeScript]
final class PaginatedData extends Data
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public PaginationMetaData $meta,
        public PaginationLinksData $links,
    ) {}

    /**
     * Links keep the current query string, so filters and sorting survive paging.
     * `$map` builds the items from the page's rows when `TItem::from()` can't
     * (the Log Aktivitas loads causers and batches for the whole page at once).
     *
     * @template TItem of Data
     *
     * @param  LengthAwarePaginator<array-key, mixed>  $page
     * @param  class-string<TItem>  $item
     * @param  (callable(Collection<array-key, mixed>): list<TItem>)|null  $map
     * @return self<TItem>
     */
    public static function fromPaginator(LengthAwarePaginator $page, string $item, ?callable $map = null): self
    {
        $page->withQueryString();

        return new self(
            items: $map !== null ? $map(collect($page->items())) : array_values(array_map(fn (mixed $row): Data => $item::from($row), $page->items())),
            meta: new PaginationMetaData(
                current_page: $page->currentPage(),
                per_page: $page->perPage(),
                total: $page->total(),
                last_page: $page->lastPage(),
            ),
            links: new PaginationLinksData(
                first: $page->url(1),
                last: $page->url($page->lastPage()),
                prev: $page->previousPageUrl(),
                next: $page->nextPageUrl(),
            ),
        );
    }
}
