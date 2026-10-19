<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn (): string => 'Off')->name('index');
