<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The list's current URL state and what the backend allows (sortable columns,
 * filters, page sizes), passed next to the `PaginatedData` prop so the
 * DataTable never offers what the server would reject. Built by
 * `Model::dataTableState($request)` (HasDataTable).
 */
#[TypeScript]
final class DataTableStateData extends Data
{
    /**
     * @param  array<string, string>  $filter
     * @param  list<int>  $per_page_options
     * @param  list<string>  $sortable
     * @param  list<DataTableFilterData>  $filters
     */
    public function __construct(
        public ?string $search,
        public ?string $sort,
        public array $filter,
        public int $per_page,
        public array $per_page_options,
        public array $sortable,
        public array $filters,
        public bool $searchable,
    ) {}
}
