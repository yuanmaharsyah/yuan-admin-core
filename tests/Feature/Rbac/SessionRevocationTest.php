<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Http\Middleware\EnsureUserIsActive;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['session.driver' => 'file']);
    Route::get('/login', fn () => 'login')->name('login');
    Route::middleware(['web', 'auth', 'active'])->group(function (): void {
        Route::get('/probe', fn () => 'ok');
        Route::get('/own-password', function () {
            /** @var User $user */
            $user = auth()->user();
            $user->update(['password' => 'GantiBaru12345']);

            return 'changed';
        });
    });
});

/**
 * A user signed in a minute ago, in a session that still works. The guard is
 * reset afterwards, so a revocation in the test acts like another admin's.
 */
function signedIn(): User
{
    $user = makeUser();
    $user->assignLevel(levelWith([], 'kasir'));
    actingAs($user)->withSession([EnsureUserIsActive::LOGIN_TIME => now()->subMinute()->getTimestamp()])->get('/probe')->assertOk();
    auth()->forgetUser();

    return $user;
}

it('logs out a session that signed in before a revocation, on the file driver', function (Closure $revoke): void {
    $user = signedIn();

    $revoke($user);

    actingAs($user->fresh() ?? $user)->get('/probe')->assertRedirect('/login');
    assertGuest();
})->with([
    'level change' => [fn (User $user) => $user->assignLevel(levelWith([], 'gudang'))],
    'deactivate' => [fn (User $user) => $user->update(['is_active' => false])],
    'password change' => [fn (User $user) => $user->update(['password' => 'GantiBaru12345'])],
]);

it('keeps a session that signed in after the revocation', function (): void {
    $user = signedIn();
    $user->update(['password' => 'GantiBaru12345']);

    actingAs($user->fresh() ?? $user)->withSession([EnsureUserIsActive::LOGIN_TIME => now()->getTimestamp()])->get('/probe')->assertOk();
});

it('keeps the current session when users change their own password', function (): void {
    $user = signedIn();

    actingAs($user)->get('/own-password')->assertOk();
    expect($user->fresh()?->sessions_invalidated_at)->not->toBeNull();
    actingAs($user->fresh() ?? $user)->get('/probe')->assertOk();
});

it('rotates the remember token so a remember cookie cannot sign back in', function (): void {
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
