<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Auth\PasswordRules;

/** The new password of a reset (Fortify's `ResetsUserPasswords` input). */
#[TypeScript]
final class ResetPasswordData extends Data
{
    public function __construct(
        public string $password,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return ['password' => ['required', 'string', PasswordRules::defaults(), 'confirmed']];
    }
}
