<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * What ExportManager downloads: a list query for the requesting user built
 * from the list's URL query. Only those URL parameters travel to the queue,
 * so the same export runs inline or in ExportJob. DataTable lists extend
 * ModuleExport; a list that isn't a DataTable model (the Log Aktivitas)
 * extends this and builds `query()` itself.
 *
 * @template TModel of Model
 *
 * @implements WithMapping<TModel>
 */
abstract class ListExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $params  The list's URL query (see params()).
     */
    final public function __construct(
        protected readonly Authenticatable $user,
        protected readonly array $params,
    ) {}

    /**
     * The URL query parameters the export reads.
     *
     * @return list<string>
     */
    public static function params(): array
    {
        return ['search', 'filter', 'sort'];
    }

    /**
     * @return Builder<TModel>
     */
    abstract public function query(): Builder;

    /**
     * One spreadsheet row of the record, in the order of headings().
     *
     * @param  TModel  $row
     * @return list<string|int|float|bool|null>
     */
    abstract protected function row(Model $row): array;

    /**
     * The row, with every text cell that a spreadsheet would read as a
     * formula (`=`, `+`, `-`, `@`, tab, carriage return) turned into text by a
     * leading `'`, so a value typed by a user can't run in Excel (CSV/formula
     * injection). Numbers are left as they are.
     *
     * @param  TModel  $row
     * @return list<string|int|float|bool|null>
     */
    final public function map(mixed $row): array
    {
        return array_map(
            fn (string|int|float|bool|null $cell): string|int|float|bool|null => is_string($cell) && preg_match('/^[=+\-@\t\r]/', $cell) === 1 ? "'".$cell : $cell,
            $this->row($row),
        );
    }
}
