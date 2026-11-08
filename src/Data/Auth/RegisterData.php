<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Auth\PasswordRules;

/** A self-registration (Fortify's `CreatesNewUsers` input); the account starts inactive. */
#[TypeScript]
final class RegisterData extends Data
{
    public function __construct(
        public string $name,
        public string $username,
        public string $email,
        public string $password,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', PasswordRules::defaults(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['name' => __('nama'), 'username' => __('username'), 'email' => __('email'), 'password' => __('kata sandi')];
    }
}
