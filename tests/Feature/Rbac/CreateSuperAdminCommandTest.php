<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

uses(RefreshDatabase::class);

/**
 * @param  array<string, string>  $options
 */
function createSuperAdmin(array $options = [], string $password = 'Rahasia12345'): PendingCommand
{
    return command('admin:create-superadmin', [
        '--name' => 'Root', '--username' => 'root', '--email' => 'root@example.test', ...$options,
    ])->expectsQuestion('Password', $password);
}

it('creates an active Super Admin, and the level when it is missing', function (): void {
    createSuperAdmin()->expectsOutputToContain('Super Admin [root] created.')->assertSuccessful();

    $user = User::query()->where('username', 'root')->sole();
    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and(Level::query()->where('is_protected', true)->count())->toBe(1);
});

it('asks for missing details', function (): void {
    command('admin:create-superadmin')
        ->expectsQuestion('Name', 'Root')
        ->expectsQuestion('Username', 'root')
        ->expectsQuestion('Email', 'root@example.test')
        ->expectsQuestion('Password', 'Rahasia12345')
        ->assertSuccessful();

    expect(User::query()->where('username', 'root')->exists())->toBeTrue();
});

it('refuses a taken username or email and a weak password', function (array $options, string $password): void {
    $taken = makeUser('Ada');
    $taken->forceFill(['username' => 'ada'])->save();

    createSuperAdmin(array_map(fn (string $value): string => str_replace('{taken}', $taken->email, $value), $options), $password)->assertFailed();

    expect(User::query()->count())->toBe(1);
})->with([
    'username' => [['--username' => 'ada'], 'Rahasia12345'],
    'email' => [['--email' => '{taken}'], 'Rahasia12345'],
    'short password' => [[], 'Rahasia1'],
    'no number' => [[], 'RahasiaSekali'],
]);
