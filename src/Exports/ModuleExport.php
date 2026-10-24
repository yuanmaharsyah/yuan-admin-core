<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Yuan\AdminCore\DataTable\DataTableBuilder;

/**
 * A module's download (`Exports/{Name}Export.php`) of a DataTable list: the
 * list query of the requesting user, with the list's current search, filters
 * and sort (ListExport has the rest).
 *
 * @template TModel of Model
 *
 * @extends ListExport<TModel>
 */
abstract class ModuleExport extends ListExport
{
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
