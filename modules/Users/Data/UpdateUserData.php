<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Modules\Users\Validation\UserRules;

/** The edit form; `lock_version` is the version it loaded (PreventsStaleUpdates). */
#[TypeScript]
final class UpdateUserData extends Data
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
        public int $lock_version,
        public array $extra = [],
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        $id = request()->route('user');
        $user = is_numeric($id) ? User::query()->withoutGlobalScopes()->find((int) $id) : null;

        return [...UserRules::for($user), 'lock_version' => ['required', 'integer', 'min:0']];
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
        return UserRules::attributes();
    }
}
