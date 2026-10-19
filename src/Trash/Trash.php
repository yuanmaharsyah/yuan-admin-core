<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Trash;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use InvalidArgumentException;
use Spatie\LaravelData\Optional;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Data\UndoData;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Soft delete, restore and permanent delete (frontend-behavior §7). Each runs
 * in one transaction and one audit batch, so a bulk action is one batch.
 * Restore and permanent delete are Super Admin only (routes from `Route::trash()`).
 */
final class Trash
{
    /**
     * Soft-deletes the models and flashes "Dipindahkan ke Sampah"; Super Admin
     * also gets the Urungkan action, which posts to `{slug}.trash.restore`.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $models  The caller has authorized `delete` on each one.
     */
    public static function delete(Collection $models, string $slug): void
    {
        self::write(fn () => $models->each->delete());

        $first = $models->first();
        $restore = "{$slug}.trash.restore";
        $user = auth()->user();

        Inertia::flash((new FlashData(
            success: __('Dipindahkan ke Sampah'),
            description: $models->count() === 1 && $first !== null
                ? self::moduleLabel($slug).' '.self::label($first)
                : __(':count item', ['count' => $models->count()]),
            undo: $user !== null && Gate::forUser($user)->allows('admin-core.superadmin') && Route::has($restore)
                ? new UndoData(href: route($restore), ids: self::ids($models))
                : new Optional,
        ))->toArray());
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<int>  $ids
     * @return int The number of rows restored.
     */
    public static function restore(string $model, array $ids): int
    {
        $rows = self::trashed($model, $ids);
        self::write(fn () => $rows->each(fn (Model $row) => method_exists($row, 'restore') && $row->restore()));

        return $rows->count();
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<int>  $ids
     * @return int The number of rows removed for good.
     */
    public static function forceDelete(string $model, array $ids): int
    {
        $rows = self::trashed($model, $ids);
        self::write(fn () => $rows->each->forceDelete());

        return $rows->count();
    }

    public static function label(Model $model): string
    {
        return method_exists($model, 'auditLabel') ? (string) $model->auditLabel() : (string) $model->getKey();
    }

    public static function moduleLabel(string $slug): string
    {
        return __(app(ModuleRegistry::class)->find($slug)->label ?? $slug);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<int>  $ids
     * @return Collection<int, Model>
     */
    private static function trashed(string $model, array $ids): Collection
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            throw new InvalidArgumentException("{$model} does not use SoftDeletes.");
        }

        return self::trashedQuery($model)->whereKey($ids)->get();
    }

    /**
     * `onlyTrashed()` without the macro, so it type-checks for any model class.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return Builder<TModel>
     */
    public static function trashedQuery(string $model): Builder
    {
        return $model::query()->withoutGlobalScope(SoftDeletingScope::class)->whereNotNull((new $model)->qualifyColumn('deleted_at'));
    }

    private static function write(callable $callback): void
    {
        DB::transaction(fn () => Audit::batch($callback(...)));
    }

    /**
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $models
     * @return list<int>
     */
    private static function ids(Collection $models): array
    {
        return array_values(array_map(intval(...), $models->modelKeys()));
    }
}
