<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Account;

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Exports\DownloadsController;
use Yuan\AdminCore\Http\HomeController;

/**
 * The signed-in user's own pages, outside `/admin` (no permission: every
 * user has them): `/home` (the landing redirect), `account.profile.*`,
 * `account.password.*`, the photo and the sessions (`account.avatar.*`,
 * `account.sessions.destroy`) and the downloads. The project calls this inside its signed-in route group.
 */
final class AccountRoutes
{
    public static function register(): void
    {
        Route::get('home', HomeController::class)->name('admin-core.home');

        Route::get('account/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
        Route::put('account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
        Route::post('account/avatar', [ProfileController::class, 'updateAvatar'])->middleware('throttle:10,1')->name('account.avatar.update');
        Route::delete('account/avatar', [ProfileController::class, 'destroyAvatar'])->name('account.avatar.destroy');
        // The photo itself: a signed URL from the shared props (any signed-in user sees a colleague's photo).
        Route::get('account/avatar/{user}', [ProfileController::class, 'showAvatar'])->whereNumber('user')->middleware('signed')->name('account.avatar.show');
        Route::delete('account/sessions', [ProfileController::class, 'signOutOthers'])->middleware('throttle:6,1')->name('account.sessions.destroy');

        Route::get('account/password', [PasswordController::class, 'edit'])->name('account.password.edit');
        Route::put('account/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('account.password.update');

        DownloadsController::routes();
    }
}
