<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Account;

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Exports\DownloadsController;
use Yuan\AdminCore\Http\HomeController;

/**
 * The signed-in user's own pages, outside `/admin` (no permission: every
 * user has them): `/home` (the landing redirect), `account.profile.*`,
 * `account.password.*` and the downloads. The project calls this inside its signed-in route group.
 */
final class AccountRoutes
{
    public static function register(): void
    {
        Route::get('home', HomeController::class)->name('admin-core.home');

        Route::get('account/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
        Route::put('account/profile', [ProfileController::class, 'update'])->name('account.profile.update');

        Route::get('account/password', [PasswordController::class, 'edit'])->name('account.password.edit');
        Route::put('account/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('account.password.update');

        DownloadsController::routes();
    }
}
