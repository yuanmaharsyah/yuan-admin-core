<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Modules\AuditLog\Http\Controllers\AuditLogController;

Route::get('/', [AuditLogController::class, 'index'])->middleware('permission:audit-log.view')->name('index');
Route::get('download', [AuditLogController::class, 'download'])->middleware('permission:audit-log.download')->name('download');
