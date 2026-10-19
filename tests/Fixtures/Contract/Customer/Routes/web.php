<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Http\Controllers\CustomerController;

Route::get('/', [CustomerController::class, 'index'])->middleware('permission:customers.view')->name('index');
