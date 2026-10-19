<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Http\Controllers\LeakyController;

// No permission:leaky.view middleware.
Route::get('/', [LeakyController::class, 'index'])->name('index');
