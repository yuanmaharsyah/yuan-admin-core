<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Auth\PasswordRules;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\Data\Users\ExtraFieldData;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Users\UserExtension;

/** The user form's choices: the levels the signed-in user may assign, the project's extra fields, and the password rules. */
#[TypeScript]
final class UserFormOptionsData extends Data
{
    /**
     * @param  list<OptionData>  $levels
     * @param  list<ExtraFieldData>  $extra_fields
     */
    public function __construct(
        public array $levels,
        public array $extra_fields,
        public string $password_hint,
    ) {}

    public static function make(): self
    {
        $levels = Level::query()
            ->when(! User::isSuperAdminViewer(auth()->user()), fn ($query) => $query->where('is_protected', false))
            ->orderByDesc('is_protected')
            ->orderBy('label')
            ->get();

        return new self(
            levels: array_values($levels->map(fn (Level $level): OptionData => new OptionData((string) $level->id, $level->label))->all()),
            extra_fields: UserExtension::fields(),
            password_hint: PasswordRules::hint(),
        );
    }
}
