<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Auth\AuthenticateUser;

uses(RefreshDatabase::class);

function attemptLogin(string $email, string $password): mixed
{
    return app(AuthenticateUser::class)(Request::create('/login', 'POST', ['email' => $email, 'password' => $password]));
}

it('returns the user for the right password', function (): void {
    $user = makeUser();

    expect(attemptLogin($user->email, 'Rahasia123')?->getAuthIdentifier())->toBe($user->id);
});

it('returns null for a wrong password or an unknown or empty identifier, writing nothing itself', function (string $email, string $password): void {
    makeUser('Budi')->forceFill(['email' => 'budi@example.test'])->save();

    expect(attemptLogin($email, $password))->toBeNull()
        ->and(ImmutableActivity::query()->count())->toBe(0);
})->with([
    'wrong password' => ['budi@example.test', 'Salah12345'],
    'unknown' => ['nobody@example.test', 'Rahasia123'],
    'empty' => ['', 'Rahasia123'],
]);

it('refuses an inactive account with the right password and logs reason=inactive', function (): void {
    $user = makeUser();
    $user->forceFill(['is_active' => false])->save();

    expect(fn () => attemptLogin($user->email, 'Rahasia123'))->toThrow(ValidationException::class, 'Akun Anda tidak aktif');

    $row = latestActivity();
    expect($row->only(['event', 'log_name', 'subject_id', 'causer_id']))->toBe(['event' => 'login_failed', 'log_name' => 'auth', 'subject_id' => $user->id, 'causer_id' => null])
        ->and($row->getProperty('meta'))->toMatchArray(['username' => $user->email, 'reason' => 'inactive'])
        ->and(json_encode(DB::table('activity_log')->first()))->not->toContain('Rahasia123');
});

it('gives an inactive account with a wrong password no hint', function (): void {
    $user = makeUser();
    $user->forceFill(['is_active' => false])->save();

    expect(attemptLogin($user->email, 'Salah12345'))->toBeNull();
});
