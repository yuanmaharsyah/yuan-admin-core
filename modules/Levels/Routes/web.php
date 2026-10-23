<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Modules\Levels\Http\Controllers\LevelsController;

// A superadmin_only module: the registry adds the `superadmin` middleware.
Route::get('/', [LevelsController::class, 'index'])->name('index');
Route::get('create', [LevelsController::class, 'create'])->name('create');
Route::post('/', [LevelsController::class, 'store'])->name('store');

Route::whereNumber('level')->group(function (): void {
    Route::get('{level}/edit', [LevelsController::class, 'edit'])->name('edit');
    Route::put('{level}', [LevelsController::class, 'update'])->name('update');
    Route::delete('{level}', [LevelsController::class, 'destroy'])->name('destroy');
    Route::get('{level}/permissions', [LevelsController::class, 'permissions'])->name('permissions');
    Route::put('{level}/permissions', [LevelsController::class, 'savePermissions'])->name('permissions.update');
});
