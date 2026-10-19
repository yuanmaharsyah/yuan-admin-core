<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * The configured activity model. Rows are append-only (spec §7.1): updating or
 * deleting one, singly or through a query, throws.
 *
 * ponytail: the query builder underneath (`DB::table('activity_log')`,
 * `toBase()`, `truncate()`) bypasses this; the Phase 04 arch test is the guard,
 * a database trigger the upgrade if that isn't enough.
 *
 * @property string|null $batch_uuid
 */
#[UseEloquentBuilder(ImmutableActivityBuilder::class)]
final class ImmutableActivity extends Activity
{
    public $guarded = ['*'];

    /**
     * @param  Builder<self>  $query
     */
    protected function performUpdate(Builder $query): bool
    {
        throw new ImmutableAuditException;
    }

    protected function performDeleteOnModel(): void
    {
        throw new ImmutableAuditException;
    }
}
