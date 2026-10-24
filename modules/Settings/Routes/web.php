<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Modules\Settings\Http\Controllers\SettingsController;

// A superadmin_only module: the registry adds the `superadmin` middleware.
Route::get('/', [SettingsController::class, 'index'])->name('index');
// POST + `_method=PUT` from the page, so branding files can be sent.
Route::put('{group}', [SettingsController::class, 'update'])->where('group', '[a-z_]+')->name('update');
