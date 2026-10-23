<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Modules\Levels\Data\LevelFormData;
use Yuan\AdminCore\Modules\Levels\Data\LevelListData;
use Yuan\AdminCore\Modules\Levels\Data\LevelListItemData;
use Yuan\AdminCore\Modules\Levels\Data\SaveMatrixData;
use Yuan\AdminCore\Modules\Levels\Data\StoreLevelData;
use Yuan\AdminCore\Modules\Levels\Data\UpdateLevelData;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Permissions\PermissionMatrix;

/**
 * Levels and their Hak Akses matrix (frontend-behavior §8.2, ui-design §4.2),
 * Super Admin only (`superadmin_only` module + the gate). The protected level
 * can't be edited, given permissions or deleted; a level with users can't be
 * deleted (spec §6.3).
 */
final class LevelsController
{
    public function index(): Response
    {
        Gate::authorize('admin-core.superadmin');

        $levels = Level::query()->withCount('users')->orderByDesc('is_protected')->orderBy('label')->get();

        return Inertia::render('Levels::Index', [
            'levels' => new LevelListData(array_values($levels->map(fn (Level $level): LevelListItemData => new LevelListItemData(
                id: $level->id,
                label: $level->label,
                description: $level->description,
                users_count: (int) $level->getAttribute('users_count'),
                is_protected: $level->is_protected,
            ))->all())),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('admin-core.superadmin');

        return Inertia::render('Levels::Create', ['breadcrumbs' => self::breadcrumbs(new BreadcrumbData(label: __('Tambah'), href: null))]);
    }

    public function store(StoreLevelData $data): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');

        $level = Level::query()->create([
            'name' => self::uniqueName($data->label),
            'guard_name' => config()->string('auth.defaults.guard'),
            'label' => $data->label,
            'description' => $data->description,
        ]);
        Inertia::flash((new FlashData(success: __('Level dibuat.'), description: __('Atur hak aksesnya sekarang.')))->toArray());

        return to_route('levels.permissions', $level);
    }

    public function edit(Level $level): Response
    {
        Gate::authorize('admin-core.superadmin');
        abort_if($level->is_protected, 403);

        return Inertia::render('Levels::Edit', [
            'level' => LevelFormData::fromLevel($level),
            'breadcrumbs' => self::breadcrumbs(new BreadcrumbData(label: $level->label, href: null)),
        ]);
    }

    public function update(UpdateLevelData $data, Level $level): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');
        abort_if($level->is_protected, 403);

        $level->updateUnlessStale(['label' => $data->label, 'description' => $data->description], $data->lock_version);
        Inertia::flash((new FlashData(success: __('Perubahan disimpan.'), description: $level->label))->toArray());

        return to_route('levels.index');
    }

    public function destroy(Level $level): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');
        abort_if($level->is_protected, 403);

        if ($level->users()->exists()) {
            Inertia::flash((new FlashData(error: __('Level masih dipakai user. Pindahkan user-nya ke level lain dulu.'), description: $level->label))->toArray());

            return back();
        }

        $level->delete();
        Inertia::flash((new FlashData(success: __('Level dihapus.'), description: $level->label))->toArray());

        return to_route('levels.index');
    }

    public function permissions(PermissionMatrix $matrix, Level $level): Response
    {
        Gate::authorize('admin-core.superadmin');
        abort_if($level->is_protected, 403);

        return Inertia::render('Levels::Permissions', [
            'matrix' => $matrix->build($level),
            'breadcrumbs' => self::breadcrumbs(new BreadcrumbData(label: $level->label, href: route('levels.edit', $level)), new BreadcrumbData(label: __('Hak Akses'), href: null)),
        ]);
    }

    public function savePermissions(PermissionMatrix $matrix, SaveMatrixData $data, Level $level): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');
        abort_if($level->is_protected, 403);

        try {
            $matrix->save($level, $data->grants);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['grants' => $exception->getMessage()]);
        }

        Inertia::flash((new FlashData(success: __('Hak akses diperbarui.'), description: $level->label))->toArray());

        return to_route('levels.permissions', $level);
    }

    /** A slug of the label, made unique among levels (`operator`, `operator-2`). */
    private static function uniqueName(string $label): string
    {
        $base = Str::slug($label) ?: 'level';
        $name = $base;

        for ($i = 2; Level::query()->where('name', $name)->exists(); $i++) {
            $name = "{$base}-{$i}";
        }

        return $name;
    }

    private static function breadcrumbs(BreadcrumbData ...$items): BreadcrumbsData
    {
        return BreadcrumbsData::of(new BreadcrumbData(label: __('Levels'), href: route('levels.index')), ...$items);
    }
}
