<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Http\Middleware\EnsureUserIsActive;

uses(RefreshDatabase::class);

it('logs login, logout and password reset with the user as causer and subject', function (string $event, object $fired, string $logName): void {
    event($fired);

    $row = latestActivity();
    expect($row->only(['event', 'log_name']))->toBe(['event' => $event, 'log_name' => $logName])
        ->and($row->causer_id)->toBe($row->subject_id)
        ->and($row->causer_id)->not->toBeNull();
})->with([
    'login' => fn () => ['login', new Login('web', makeUser(), false), 'auth'],
    'logout' => fn () => ['logout', new Logout('web', makeUser()), 'auth'],
    'password reset' => fn () => ['password_reset', new PasswordReset(makeUser()), 'system'],
]);

it('logs a failed login with the identifier only, on the matched user', function (): void {
    $user = makeUser();

    event(new Failed('web', $user, ['email' => $user->email, 'password' => 'SalahSekali9']));

    $row = latestActivity();
    expect($row->only(['event', 'log_name', 'causer_id', 'subject_id']))
        ->toBe(['event' => 'login_failed', 'log_name' => 'auth', 'causer_id' => null, 'subject_id' => $user->id])
        ->and($row->getProperty('meta'))->toBe(['username' => $user->email, 'subject_label' => '#'.$user->id])
        ->and(json_encode(DB::table('activity_log')->first()))->not->toContain('SalahSekali9');
});

it('finds the account of a failed login when the guard did not', function (): void {
    $user = makeUser();

    event(new Failed('web', null, ['email' => $user->email, 'password' => 'x']));

    expect(latestActivity()->subject_id)->toBe($user->id);
});

it('stamps last_login_at and the session version on login', function (): void {
    $user = makeUser();
    session()->start();
    request()->setLaravelSession(session()->driver());

    event(new Login('web', $user, false));

    expect($user->fresh()?->getAttribute('last_login_at'))->not->toBeNull()
        ->and(session(EnsureUserIsActive::SESSION_VERSION))->toBe(0);
});

it('logs a failed login for an unknown identifier without a subject', function (): void {
    event(new Failed('web', null, ['email' => 'nobody@example.test', 'password' => 'x']));

    expect(latestActivity()->subject_id)->toBeNull()
        ->and(latestActivity()->getProperty('meta'))->toBe(['username' => 'nobody@example.test']);
});

it('logs a lockout with the identifier only', function (): void {
    event(new Lockout(Request::create('/login', 'POST', ['email' => 'budi@example.test', 'password' => 'SalahSekali9'])));

    expect(latestActivity()->only(['event', 'log_name']))->toBe(['event' => 'login_locked', 'log_name' => 'auth'])
        ->and(latestActivity()->getProperty('meta'))->toBe(['username' => 'budi@example.test'])
        ->and(json_encode(DB::table('activity_log')->first()))->not->toContain('SalahSekali9');
});
