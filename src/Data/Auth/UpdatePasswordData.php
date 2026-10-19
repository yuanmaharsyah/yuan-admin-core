<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Auth\PasswordRules;

/** A signed-in user's own password change. */
#[TypeScript]
final class UpdatePasswordData extends Data
{
    public function __construct(
        public string $current_password,
        public string $password,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', PasswordRules::defaults(), 'confirmed', 'different:current_password'],
        ];
    }
}
