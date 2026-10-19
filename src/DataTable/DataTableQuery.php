<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** The `search` and `per_page` URL parameters every list shares (frontend-behavior §5). */
final class DataTableQuery
{
    /** @var list<int> */
    public const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const int PER_PAGE_DEFAULT = 25;

    public static function search(Request $request): ?string
    {
        $term = trim($request->string('search')->toString());

        return $term === '' ? null : $term;
    }

    /** One of the allowed sizes; anything else falls back to the default, so it never exceeds 100. */
    public static function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', self::PER_PAGE_DEFAULT);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_DEFAULT;
    }

    /**
     * `search` as a LIKE on any of the columns. `%` and `_` typed by the user
     * act as wildcards: harmless, since the query is already scoped.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     */
    public static function applySearch(Builder $query, array $columns, ?string $term): void
    {
        if ($term === null || $columns === []) {
            return;
        }

        $query->where(function (Builder $where) use ($columns, $term): void {
            foreach ($columns as $column) {
                $where->orWhereLike($column, "%{$term}%");
            }
        });
    }
}
