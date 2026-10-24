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
use Yuan\AdminCore\DataTable\VisibilityScope;

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
        VisibilityScope::applied(self::class, $viewer);

        if (method_exists($viewer, 'isSuperAdmin') && $viewer->isSuperAdmin()) {
            return;
        }

        $morph = self::userMorph();

        foreach (['causer', 'subject'] as $side) {
            $query->where(fn (Builder $rows) => $rows
                ->whereNull("{$side}_id")
                ->orWhere("{$side}_type", '!=', $morph)
                ->orWhereNotIn("{$side}_id", fn (QueryBuilder $ids) => self::superAdminQuery($ids, $morph)));
        }
    }

    /**
     * The rows `visibleTo()` hides from a non-SA viewer: caused by or about
     * one of these user accounts (`superAdminIds()`). A row with both sides
     * matching is still one row, so subtracting these counts is exact.
     *
     * @param  Builder<self>  $query
     * @param  list<int>  $userIds
     */
    #[Scope]
    protected function involvingUsers(Builder $query, array $userIds): void
    {
        $morph = self::userMorph();

        $query->where(fn (Builder $rows) => $rows
            ->where(fn (Builder $causer) => $causer->where('causer_type', $morph)->whereIn('causer_id', $userIds))
            ->orWhere(fn (Builder $subject) => $subject->where('subject_type', $morph)->whereIn('subject_id', $userIds)));
    }

    /**
     * The ids of the current Super Admin accounts.
     *
     * @return list<int>
     */
    public static function superAdminIds(): array
    {
        return array_values(array_map(intval(...), self::superAdminQuery((new self)->getConnection()->query(), self::userMorph())->pluck('model_has_roles.model_id')->all()));
    }

    public static function userMorph(): string
    {
        $model = app(config()->string('auth.providers.users.model'));

        if (! $model instanceof Model) {
            throw new LogicException('auth.providers.users.model must be an Eloquent model for the SA visibility rule.');
        }

        return $model->getMorphClass();
    }

    private static function superAdminQuery(QueryBuilder $ids, string $morph): QueryBuilder
    {
        return $ids->select('model_has_roles.model_id')
            ->from('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.is_protected', true)
            ->where('model_has_roles.model_type', $morph);
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
