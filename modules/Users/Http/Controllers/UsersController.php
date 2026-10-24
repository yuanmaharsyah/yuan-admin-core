<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\Optional;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Yuan\AdminCore\Audit\ActivityHistory;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\Data\SelectionData;
use Yuan\AdminCore\Exports\ExportManager;
use Yuan\AdminCore\Modules\Users\Data\ResetPasswordData;
use Yuan\AdminCore\Modules\Users\Data\StoreUserData;
use Yuan\AdminCore\Modules\Users\Data\UpdateUserData;
use Yuan\AdminCore\Modules\Users\Data\UserData;
use Yuan\AdminCore\Modules\Users\Data\UserFormOptionsData;
use Yuan\AdminCore\Modules\Users\Data\UserListItemData;
use Yuan\AdminCore\Modules\Users\Exports\UsersExport;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Modules\Users\Services\UserAccounts;
use Yuan\AdminCore\Trash\Trash;

/**
 * Users (frontend-behavior §8.1, ui-design §4.1). Every lookup goes through
 * `visibleTo()`, so a non-Super Admin gets a 404 for a Super Admin account.
 */
final class UsersController
{
    public function __construct(private readonly UserAccounts $accounts) {}

    public function index(Request $request): Response
    {
        $model = UserAccounts::model();
        Gate::authorize('viewAny', $model);

        return Inertia::render('Users::Index', [
            'users' => PaginatedData::fromPaginator($model::query()->visibleTo($request->user())->with('roles')->dataTable($request), UserListItemData::class),
            'table' => $model::dataTableState($request),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', UserAccounts::model());

        return Inertia::render('Users::Create', [
            'options' => UserFormOptionsData::make(),
            'breadcrumbs' => self::breadcrumbs(new BreadcrumbData(label: __('Tambah'), href: null)),
        ]);
    }

    public function store(StoreUserData $data): RedirectResponse
    {
        Gate::authorize('create', UserAccounts::model());

        $user = $this->accounts->create($data);
        Inertia::flash((new FlashData(success: __('User dibuat.'), description: __('Kata sandi harus diganti saat pertama masuk.')))->toArray());

        return to_route('users.show', $user);
    }

    public function show(Request $request, int $user): Response
    {
        $model = self::find($request, $user);
        Gate::authorize('view', $model);

        return Inertia::render('Users::Show', [
            'user' => UserData::fromModel($model),
            'options' => UserFormOptionsData::make(),
            'history' => ActivityHistory::prop($model, $request->user()),
            'breadcrumbs' => self::breadcrumbs(new BreadcrumbData(label: $model->name, href: null)),
        ]);
    }

    public function edit(Request $request, int $user): Response
    {
        $model = self::find($request, $user);
        Gate::authorize('update', $model);

        return Inertia::render('Users::Edit', [
            'user' => UserData::fromModel($model),
            'options' => UserFormOptionsData::make(),
            'breadcrumbs' => self::breadcrumbs(
                new BreadcrumbData(label: $model->name, href: route('users.show', $model)),
                new BreadcrumbData(label: __('Edit'), href: null),
            ),
        ]);
    }

    public function update(Request $request, UpdateUserData $data, int $user): RedirectResponse
    {
        $model = self::find($request, $user);
        Gate::authorize('update', $model);

        $this->accounts->update($model, $data, $request->user());
        Inertia::flash((new FlashData(success: __('Perubahan disimpan.'), description: $model->name))->toArray());

        return to_route('users.show', $model);
    }

    public function activate(Request $request, int $user): RedirectResponse
    {
        return $this->setActive($request, $user, true);
    }

    public function deactivate(Request $request, int $user): RedirectResponse
    {
        return $this->setActive($request, $user, false);
    }

    public function resetPassword(Request $request, ResetPasswordData $data, int $user): RedirectResponse
    {
        $model = self::find($request, $user);
        Gate::authorize('update', $model);
        UserAccounts::refuseSelf($model, $request->user());

        $password = $this->accounts->resetPassword($model, $data->mode === 'set' ? $data->password : null);
        Inertia::flash((new FlashData(
            success: __('Kata sandi direset.'),
            description: __('Kata sandi harus diganti saat masuk berikutnya.'),
            secret: $data->mode === 'generate' ? $password : new Optional,
        ))->toArray());

        return back();
    }

    public function destroy(Request $request, int $user): RedirectResponse
    {
        $model = self::find($request, $user);
        Gate::authorize('delete', $model);
        UserAccounts::refuseSelf($model, $request->user());

        Trash::delete($model->newCollection([$model]), 'users');

        return to_route('users.index');
    }

    public function bulkDestroy(Request $request, SelectionData $data): RedirectResponse
    {
        $users = UserAccounts::model()::query()->visibleTo($request->user())->whereKey($data->ids)->get();
        $users->each(function (User $user) use ($request): void {
            Gate::authorize('delete', $user);
            UserAccounts::refuseSelf($user, $request->user());
        });

        Trash::delete($users, 'users');

        return back();
    }

    public function download(Request $request): SymfonyResponse
    {
        Gate::authorize('download', UserAccounts::model());

        return ExportManager::for(UsersExport::class, $request);
    }

    private function setActive(Request $request, int $user, bool $active): RedirectResponse
    {
        $model = self::find($request, $user);
        Gate::authorize('update', $model);
        UserAccounts::refuseSelf($model, $request->user());

        $this->accounts->setActive($model, $active);
        Inertia::flash((new FlashData(success: $active ? __('User diaktifkan.') : __('User dinonaktifkan.'), description: $model->name))->toArray());

        return back();
    }

    private static function find(Request $request, int $id): User
    {
        return UserAccounts::model()::query()->visibleTo($request->user())->with('roles')->findOrFail($id);
    }

    private static function breadcrumbs(BreadcrumbData ...$items): BreadcrumbsData
    {
        return BreadcrumbsData::of(new BreadcrumbData(label: __('Users'), href: route('users.index')), ...$items);
    }
}
