<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Yuan\AdminCore\Data\DataTableFilterData;

/**
 * A model that can be listed with `->dataTable($request)`. `HasDataTable`
 * implements it with empty defaults; the model overrides what it allows.
 */
interface DataTableModel
{
    /**
     * Columns `search` matches with LIKE (any of them).
     *
     * @return list<string>
     */
    public function dataTableSearch(): array;

    /**
     * Columns the user may sort by.
     *
     * @return list<string>
     */
    public function dataTableSorts(): array;

    /**
     * Exact-match filters, with the options the toolbar offers.
     *
     * @return list<DataTableFilterData>
     */
    public function dataTableFilters(): array;

    /** Sort used when the URL has none. */
    public function dataTableDefaultSort(): string;
}
