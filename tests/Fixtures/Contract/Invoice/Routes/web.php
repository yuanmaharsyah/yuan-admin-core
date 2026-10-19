<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Http\Controllers\InvoiceController;

Route::get('/', [InvoiceController::class, 'index'])->middleware('permission:invoices.view')->name('index');
Route::post('/', [InvoiceController::class, 'store'])->middleware('permission:invoices.create')->name('store');
Route::post('{invoice}/approve', [InvoiceController::class, 'approve'])->middleware('permission:invoices.approve')->name('approve');
