<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LogicException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Yuan\AdminCore\Data\DataTableFilterData;

/**
 * The Eloquent builder of every `HasDataTable` model, so the list query reads
 * `Model::query()->visibleTo($user)->dataTable($request)` and stays typed.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
final class DataTableBuilder extends Builder
{
    /**
     * Search, filter, sort and paginate from the URL (frontend-behavior §5).
     * An undeclared filter or sort is a 400.
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function dataTable(Request $request): LengthAwarePaginator
    {
        return $this->dataTableQuery($request)->paginate(DataTableQuery::perPage($request));
    }

    /**
     * The list's search, filters and sort without paging: what a download of
     * the current list exports.
     *
     * @return Builder<TModel>
     */
    public function dataTableQuery(Request $request): Builder
    {
        $model = $this->getModel();

        if (! $model instanceof DataTableModel) {
            throw new LogicException($model::class.' does not implement DataTableModel.');
        }

        DataTableQuery::applySearch($this, $model->dataTableSearch(), DataTableQuery::search($request));

        /** @var Builder<TModel> */
        return QueryBuilder::for($this, $request)
            ->allowedFilters(...array_map(fn (DataTableFilterData $filter): AllowedFilter => AllowedFilter::exact($filter->key, $filter->column ?? $filter->key), $model->dataTableFilters()))
            ->allowedSorts(...$model->dataTableSorts())
            ->defaultSort($model->dataTableDefaultSort())
            ->getEloquentBuilder();
    }
}
