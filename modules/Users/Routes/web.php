<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Modules\Users\Http\Controllers\UsersController;
use Yuan\AdminCore\Modules\Users\Services\UserAccounts;
use Yuan\AdminCore\Trash\TrashController;

TrashController::routes(UserAccounts::model());

Route::get('/', [UsersController::class, 'index'])->middleware('permission:users.view')->name('index');
Route::get('download', [UsersController::class, 'download'])->middleware('permission:users.download')->name('download');
Route::get('create', [UsersController::class, 'create'])->middleware('permission:users.create')->name('create');
Route::post('/', [UsersController::class, 'store'])->middleware('permission:users.create')->name('store');
Route::delete('/', [UsersController::class, 'bulkDestroy'])->middleware('permission:users.delete')->name('bulk-destroy');

Route::whereNumber('user')->group(function (): void {
    Route::get('{user}', [UsersController::class, 'show'])->middleware('permission:users.view')->name('show');
    Route::get('{user}/edit', [UsersController::class, 'edit'])->middleware('permission:users.edit')->name('edit');
    Route::put('{user}', [UsersController::class, 'update'])->middleware('permission:users.edit')->name('update');
    Route::post('{user}/activate', [UsersController::class, 'activate'])->middleware('permission:users.edit')->name('activate');
    Route::post('{user}/deactivate', [UsersController::class, 'deactivate'])->middleware('permission:users.edit')->name('deactivate');
    Route::post('{user}/reset-password', [UsersController::class, 'resetPassword'])->middleware(['permission:users.edit', 'throttle:6,1'])->name('reset-password');
    Route::delete('{user}', [UsersController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
});
