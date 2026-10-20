<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Yuan\AdminCore\DataTable\DataTableBuilder;

/**
 * A module's download (`Exports/{Name}Export.php`): the list query of the
 * requesting user, with the list's current search, filters and sort. Only
 * the URL parameters travel to the queue, so the same export runs inline or
 * in ExportJob.
 *
 * @template TModel of Model
 *
 * @implements WithMapping<TModel>
 */
abstract class ModuleExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $params  The list's URL query (`search`, `filter`, `sort`).
     */
    final public function __construct(
        protected readonly Authenticatable $user,
        protected readonly array $params,
    ) {}

    /**
     * The list's base query for this user: `Model::query()->visibleTo($this->user)->with(…)`.
     *
     * @return DataTableBuilder<TModel>
     */
    abstract protected function baseQuery(): DataTableBuilder;

    /**
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->baseQuery()->dataTableQuery(Request::create('/', 'GET', $this->params));
    }
}
