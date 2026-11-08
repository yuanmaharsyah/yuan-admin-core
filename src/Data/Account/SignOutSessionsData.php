<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Keluar dari perangkat lain": asks for the current password first. */
#[TypeScript]
final class SignOutSessionsData extends Data
{
    public function __construct(
        public string $password,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return ['password' => ['required', 'string', 'current_password']];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['password' => __('kata sandi')];
    }
}
