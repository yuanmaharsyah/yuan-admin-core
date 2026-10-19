<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;
use Yuan\AdminCore\Trash\TrashController;

Route::get('/', fn () => Inertia::render('Demo::Index'))->name('index');

TrashController::routes(DemoItem::class);
