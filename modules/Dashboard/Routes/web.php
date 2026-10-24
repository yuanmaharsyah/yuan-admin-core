<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Modules\Dashboard\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('index');
