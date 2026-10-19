<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Http\Middleware\EnsureUserIsActive;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['session.driver' => 'file']);
    Route::get('/login', fn () => 'login')->name('login');
    Route::middleware(['web', 'active'])->group(function (): void {
        Route::post('/sign-in/{user}', function (User $user) {
            auth()->login($user);

            return 'signed in';
        });
        Route::post('/logout', function () {
            auth()->logout();

            return 'signed out';
        })->name('logout');
        Route::middleware('auth')->group(function (): void {
            Route::get('/probe', fn () => 'ok');
            Route::get('/own-password', function () {
                /** @var User $user */
                $user = auth()->user();
                $user->update(['password' => 'GantiBaru12345']);

                return 'changed';
            });
        });
    });
});

/**
 * A user signed in through a real login (Login event), in a session that works.
 */
function signedIn(): User
{
    $user = makeUser();
    $user->assignLevel(levelWith([], 'kasir'));
    post("/sign-in/{$user->id}")->assertOk();
    get('/probe')->assertOk();

    return $user;
}

/**
 * Runs a change as another signed-in admin, then lets the next request
 * resolve the user from the session again.
 */
function asAnotherAdmin(Closure $change): void
{
    auth()->setUser(makeUser('Admin'));
    $change();
    auth()->forgetUser();
}

it('stores the user version in the session at login', function (): void {
    $user = signedIn();

    expect(session(EnsureUserIsActive::SESSION_VERSION))->toBe($user->fresh()?->session_version);
});

it('logs out a session after a revocation, on the file driver', function (Closure $revoke): void {
    $user = signedIn();

    asAnotherAdmin(fn () => $revoke($user));

    get('/probe')->assertRedirect('/login');
    assertGuest();
    expect(session('inertia.flash_data'))->toMatchArray(['error' => 'Sesi Anda telah berakhir. Silakan masuk kembali.']);
})->with([
    'level change' => [fn (User $user) => $user->assignLevel(levelWith([], 'gudang'))],
    'deactivate' => [fn (User $user) => $user->update(['is_active' => false])],
    'password change' => [fn (User $user) => $user->update(['password' => 'GantiBaru12345'])],
]);

it('logs out a session revoked in the same second it signed in', function (): void {
    Carbon::setTestNow(now());
    $user = signedIn();

    asAnotherAdmin(fn () => $user->assignLevel(levelWith([], 'gudang')));

    get('/probe')->assertRedirect('/login');
    assertGuest();
});

it('keeps the current session when users change their own password', function (): void {
    $user = signedIn();
    $before = $user->fresh()?->session_version;

    get('/own-password')->assertOk();

    expect($user->fresh()?->session_version)->toBe($before + 1);
    get('/probe')->assertOk();
    assertAuthenticatedAs($user->fresh() ?? $user);
});

it('raises the version atomically, even from a stale copy of the user', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith([], 'kasir'));
    $stale = User::query()->findOrFail($user->id);

    $user->assignLevel(levelWith([], 'gudang'));
    $stale->update(['password' => 'GantiBaru12345']);

    expect($user->fresh()?->session_version)->toBe(2)
        ->and($stale->session_version)->toBe(2);
});

it('lets a session with no stored version through only until the first revocation', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith([], 'kasir'));
    actingAs($user)->get('/probe')->assertOk();

    asAnotherAdmin(fn () => $user->assignLevel(levelWith([], 'gudang')));

    actingAs($user->fresh() ?? $user)->get('/probe')->assertRedirect('/login');
});

it('lets a revoked session reach logout', function (): void {
    $user = signedIn();
    asAnotherAdmin(fn () => $user->assignLevel(levelWith([], 'gudang')));

    post('/logout')->assertOk()->assertSee('signed out');
    assertGuest();
});

it('lets guests through', function (): void {
    post('/logout')->assertOk();
    get('/probe')->assertRedirect('/login');
});

it('rotates the remember token so a remember-me cookie cannot sign back in', function (): void {
    $user = signedIn();
    $token = $user->fresh()?->getRememberToken();

    $user->assignLevel(levelWith([], 'gudang'));

    expect($user->fresh()?->getRememberToken())->not->toBe($token)
        ->and($user->fresh()?->getRememberToken())->toBeString();
});

it('also deletes stored sessions on the database driver', function (): void {
    config(['session.driver' => 'database']);
    $user = makeUser();
    $user->assignLevel(levelWith([], 'kasir'));
    foreach (['a', 'b'] as $id) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    }
    DB::table('sessions')->insert(['id' => 'other', 'user_id' => $user->id + 1, 'payload' => '', 'last_activity' => time()]);

    $user->assignLevel(levelWith([], 'gudang'));

    expect(DB::table('sessions')->pluck('id')->all())->toBe(['other']);
});

it('does not stamp a version for a non-session login', function (): void {
    event(new Login('web', makeUser(), false));

    expect(session()->has(EnsureUserIsActive::SESSION_VERSION))->toBeFalse();
});
