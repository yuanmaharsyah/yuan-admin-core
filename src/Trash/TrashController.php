<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Trash;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Data\DataTableStateData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\Data\SelectionData;
use Yuan\AdminCore\Data\TrashItemData;
use Yuan\AdminCore\Data\TrashPageData;
use Yuan\AdminCore\DataTable\DataTableModel;
use Yuan\AdminCore\DataTable\DataTableQuery;

/**
 * The Super Admin Trash of one module (frontend-behavior §7): list, restore,
 * permanent delete. A module adds it with `TrashController::routes(Model::class)`
 * in its routes file; the model and slug come from the route.
 */
final class TrashController
{
    /**
     * Registers `{slug}.trash`, `{slug}.trash.restore` and `{slug}.trash.destroy`
     * inside the module's route group. Call it before a `{model}` wildcard route.
     *
     * @param  class-string<Model>  $model
     */
    public static function routes(string $model): void
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            throw new InvalidArgumentException("Trash of {$model} needs SoftDeletes.");
        }

        if (! is_subclass_of($model, DataTableModel::class)) {
            throw new InvalidArgumentException("Trash of {$model} needs DataTableModel.");
        }

        Route::middleware('superadmin')->group(function () use ($model): void {
            Route::get('trash', [self::class, 'index'])->defaults('model', $model)->name('trash');
            Route::post('trash/restore', [self::class, 'restore'])->defaults('model', $model)->name('trash.restore');
            Route::delete('trash', [self::class, 'destroy'])->defaults('model', $model)->name('trash.destroy');
        });
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function index(Request $request, string $model): Response
    {
        Gate::authorize('admin-core.superadmin');

        $slug = self::slug($request);
        $perPage = DataTableQuery::perPage($request);
        $search = DataTableQuery::search($request);
        $columns = self::searchColumns(new $model);
        $query = Trash::trashedQuery($model);
        DataTableQuery::applySearch($query, $columns, $search);
        $page = $query->latest('deleted_at')->paginate($perPage);
        $label = Trash::moduleLabel($slug);

        return Inertia::render('Core::Trash/Index', [
            'items' => PaginatedData::fromPaginator($page, TrashItemData::class),
            'table' => new DataTableStateData(
                search: $search,
                sort: null,
                filter: [],
                per_page: $perPage,
                per_page_options: DataTableQuery::PER_PAGE_OPTIONS,
                sortable: [],
                filters: [],
                searchable: $columns !== [],
            ),
            'trash' => new TrashPageData(
                module_label: $label,
                index_href: route("{$slug}.index"),
                restore_href: route("{$slug}.trash.restore"),
                destroy_href: route("{$slug}.trash.destroy"),
            ),
            'breadcrumbs' => BreadcrumbsData::of(
                new BreadcrumbData(label: $label, href: route("{$slug}.index")),
                new BreadcrumbData(label: __('Sampah'), href: null),
            ),
        ]);
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function restore(SelectionData $data, string $model): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');

        $count = Trash::restore($model, $data->ids);
        Inertia::flash((new FlashData(success: trans_choice('{1} Data dipulihkan|[2,*] :count data dipulihkan', $count)))->toArray());

        return back();
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function destroy(Request $request, SelectionData $data, string $model): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');

        $count = Trash::forceDelete($model, $data->ids);
        Inertia::flash((new FlashData(success: trans_choice('{1} Data dihapus permanen|[2,*] :count data dihapus permanen', $count)))->toArray());

        return to_route(self::slug($request).'.trash');
    }

    /**
     * @return list<string>
     */
    private static function searchColumns(Model $model): array
    {
        return $model instanceof DataTableModel ? $model->dataTableSearch() : [];
    }

    /** The module slug, from the route name `{slug}.trash…`. */
    private static function slug(Request $request): string
    {
        return strstr((string) $request->route()?->getName(), '.trash', true) ?: '';
    }
}
