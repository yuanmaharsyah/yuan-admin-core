<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The signed-in user's own name and email. */
#[TypeScript]
final class UpdateProfileData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public int $lock_version,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore(auth()->id())],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['name' => __('nama'), 'email' => __('email')];
    }
}
