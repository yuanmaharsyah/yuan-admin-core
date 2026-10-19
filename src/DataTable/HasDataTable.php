<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yuan\AdminCore\Data\DataTableFilterData;
use Yuan\AdminCore\Data\DataTableStateData;

/**
 * Server-side lists (frontend-behavior §5) for a model implementing
 * `DataTableModel`: `Model::query()->visibleTo($user)->dataTable($request)`.
 * The URL carries `search`, `filter[key]`, `sort` (`col` / `-col`), `page` and
 * `per_page`. The model overrides the `dataTable*()` methods it needs.
 */
trait HasDataTable
{
    /**
     * @return list<string>
     */
    public function dataTableSearch(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function dataTableSorts(): array
    {
        return [];
    }

    /**
     * @return list<DataTableFilterData>
     */
    public function dataTableFilters(): array
    {
        return [];
    }

    /** Newest first. */
    public function dataTableDefaultSort(): string
    {
        return '-'.$this->getKeyName();
    }

    /**
     * @param  QueryBuilder  $query
     * @return DataTableBuilder<$this>
     */
    public function newEloquentBuilder($query): DataTableBuilder
    {
        return (new DataTableBuilder($query))->setModel($this);
    }

    /** The `table` prop for the list page. */
    public static function dataTableState(Request $request): DataTableStateData
    {
        $model = new static;
        $sortable = $model->dataTableSorts();
        $filters = $model->dataTableFilters();
        $keys = array_column($filters, 'key');
        $sort = $request->string('sort')->toString();
        $filter = [];

        foreach ($request->array('filter') as $key => $value) {
            if (in_array($key, $keys, true) && is_scalar($value) && (string) $value !== '') {
                $filter[(string) $key] = (string) $value;
            }
        }

        return new DataTableStateData(
            search: DataTableQuery::search($request),
            sort: in_array(ltrim($sort, '-'), $sortable, true) ? $sort : null,
            filter: $filter,
            per_page: DataTableQuery::perPage($request),
            per_page_options: DataTableQuery::PER_PAGE_OPTIONS,
            sortable: $sortable,
            filters: $filters,
            searchable: $model->dataTableSearch() !== [],
        );
    }
}
