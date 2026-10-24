<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Audit\AuditCategory;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterData;

/**
 * The Log Aktivitas queries, always through `visibleTo($viewer)` (the SA
 * visibility rule): `scope()` is what the summary counts (range, user,
 * module), `events()` the list (plus event group, batch and search).
 */
final class AuditQuery
{
    /**
     * @return Builder<ImmutableActivity>
     */
    public static function scope(AuditFilterData $filters, Authenticatable $viewer): Builder
    {
        return self::filter(ImmutableActivity::query()->visibleTo($viewer), $filters);
    }

    /**
     * Range, user and module only. Callers bring the visibility: `scope()`
     * does, and the summary subtracts the SA rows (`AuditSummary`).
     *
     * @param  Builder<ImmutableActivity>  $query
     * @return Builder<ImmutableActivity>
     */
    public static function filter(Builder $query, AuditFilterData $filters): Builder
    {
        [$from, $to] = $filters->period();
        // Whole days: the virtual created_on keeps the summary's grouping on its covering index.
        $query->whereBetween('created_on', [$from->toDateString(), $to->toDateString()]);

        if (($user = $filters->user()) !== null) {
            $query->where('causer_type', ImmutableActivity::userMorph())->where('causer_id', $user);
        }

        if (($module = $filters->module()) !== null) {
            $query->where('log_name', $module);
        }

        return $query;
    }

    /**
     * @return Builder<ImmutableActivity>
     */
    public static function events(AuditFilterData $filters, Authenticatable $viewer): Builder
    {
        $query = self::scope($filters, $viewer);

        if (($category = $filters->category()) !== null) {
            self::whereCategory($query, $category);
        }

        if (($batch = $filters->batch()) !== null) {
            $query->where('batch_uuid', $batch);
        }

        if ($filters->search !== null && $filters->search !== '') {
            // JSON_UNQUOTE gives a binary collation on MySQL, so compare in lower case.
            $label = DB::connection($query->getModel()->getConnectionName())->getDriverName() === 'sqlite'
                ? DB::raw("LOWER(json_extract(properties, '$.meta.subject_label'))")
                : DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.meta.subject_label')))");
            $query->where($label, 'like', '%'.mb_strtolower($filters->search).'%');
        }

        return $query->latest('id');
    }

    /**
     * @param  Builder<ImmutableActivity>  $query
     */
    public static function whereCategory(Builder $query, AuditCategory $category): void
    {
        if ($category !== AuditCategory::Other) {
            $query->whereIn('event', $category->events());

            return;
        }

        $grouped = array_merge(...array_map(fn (AuditCategory $group): array => $group->events(), AuditCategory::cases()));
        $query->where(fn (Builder $rows) => $rows->whereNull('event')->orWhereNotIn('event', $grouped));
    }
}
