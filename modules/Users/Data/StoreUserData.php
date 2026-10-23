<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Auth\PasswordRules;
use Yuan\AdminCore\Modules\Users\Validation\UserRules;

/** A new user: the account starts with `must_change_password`. */
#[TypeScript]
final class StoreUserData extends Data
{
    /**
     * @param  array<string, string|int|float|null>  $extra
     */
    public function __construct(
        public string $name,
        public ?string $username,
        public string $email,
        public int $level_id,
        public bool $is_active,
        public string $password,
        public array $extra = [],
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [...UserRules::for(null), 'password' => ['required', 'string', PasswordRules::defaults(), 'confirmed']];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return UserRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [...UserRules::attributes(), 'password' => __('kata sandi')];
    }
}
