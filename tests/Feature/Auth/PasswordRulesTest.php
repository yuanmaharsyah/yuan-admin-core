<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Yuan\AdminCore\Auth\PasswordRules;

function passes(string $password): bool
{
    return Validator::make(['password' => $password], ['password' => Password::defaults()])->passes();
}

it('sets the spec §8 rules as Password::defaults(): 10 characters, mixed case and a number', function (string $password, bool $valid): void {
    expect(passes($password))->toBe($valid);
})->with([
    'valid' => ['Rahasia12345', true],
    'nine characters' => ['Rahasia12', false],
    'no uppercase' => ['rahasia12345', false],
    'no lowercase' => ['RAHASIA12345', false],
    'no number' => ['RahasiaSekali', false],
]);

it('follows admin-core.passwords and words the rules for the hint', function (): void {
    config(['admin-core.passwords.min' => 12]);

    expect(passes('Rahasia12345'))->toBeTrue()
        ->and(passes('Rahasia1234'))->toBeFalse()
        ->and(PasswordRules::hint())->toContain('12');
});
