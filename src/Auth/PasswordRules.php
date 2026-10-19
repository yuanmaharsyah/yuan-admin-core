<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Auth;

use Illuminate\Validation\Rules\Password;

/**
 * The password rules of spec §8, set as `Password::defaults()` by the core
 * provider. A project changes them through `admin-core.passwords`, or replaces
 * them by calling `Password::defaults()` in its own provider.
 */
final class PasswordRules
{
    public static function defaults(): Password
    {
        $rules = Password::min(config()->integer('admin-core.passwords.min', 10))->mixedCase()->numbers();

        return config()->boolean('admin-core.passwords.uncompromised') ? $rules->uncompromised() : $rules;
    }

    /**
     * The rules in words, shown under new-password fields.
     */
    public static function hint(): string
    {
        return __('Minimal :min karakter, dengan huruf besar, huruf kecil, dan angka.', ['min' => config()->integer('admin-core.passwords.min', 10)]);
    }
}
