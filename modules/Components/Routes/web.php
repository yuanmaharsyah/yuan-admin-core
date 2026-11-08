<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Modules\Components\Http\Controllers\ComponentsController;

// A superadmin_only module: the registry adds the `superadmin` middleware.
Route::get('/', [ComponentsController::class, 'index'])->name('index');
