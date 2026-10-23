<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Auth\PasswordRules;

/** An admin's password reset: a generated temporary password, or one the admin types. */
#[TypeScript]
final class ResetPasswordData extends Data
{
    /**
     * @param  'generate'|'set'  $mode
     */
    public function __construct(
        public string $mode,
        public ?string $password = null,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'mode' => ['required', 'in:generate,set'],
            'password' => ['nullable', 'required_if:mode,set', 'string', PasswordRules::defaults(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['password' => __('kata sandi')];
    }
}
