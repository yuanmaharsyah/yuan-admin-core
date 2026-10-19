<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LogicException;
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
     * The SA visibility rule (spec §7.1): a non-SA viewer never sees rows
     * caused by or about a Super Admin account. Every audit read goes through it.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, Authenticatable $viewer): void
    {
        if (method_exists($viewer, 'isSuperAdmin') && $viewer->isSuperAdmin()) {
            return;
        }

        $model = app(config()->string('auth.providers.users.model'));

        if (! $model instanceof Model) {
            throw new LogicException('auth.providers.users.model must be an Eloquent model for the SA visibility rule.');
        }

        $morph = $model->getMorphClass();
        $superAdmins = fn (QueryBuilder $ids) => $ids->select('model_has_roles.model_id')
            ->from('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.is_protected', true)
            ->where('model_has_roles.model_type', $morph);

        foreach (['causer', 'subject'] as $side) {
            $query->where(fn (Builder $rows) => $rows
                ->whereNull("{$side}_id")
                ->orWhere("{$side}_type", '!=', $morph)
                ->orWhereNotIn("{$side}_id", $superAdmins));
        }
    }

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
