<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\Users\ExtraValueData;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Users\UserExtension;

/** One user, for the Show and Edit pages; `extra` holds the project's extra fields. */
#[TypeScript]
final class UserData extends Data
{
    /**
     * @param  list<ExtraValueData>  $extra
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $username,
        public string $email,
        public ?int $level_id,
        public ?string $level_label,
        public bool $is_active,
        public bool $must_change_password,
        public ?string $last_login_at,
        public string $created_at,
        public int $lock_version,
        public bool $is_self,
        public array $extra,
    ) {}

    public static function fromModel(User $user): self
    {
        $viewer = auth()->user();

        return new self(
            id: $user->id,
            name: $user->name,
            username: $user->username,
            email: $user->email,
            level_id: $user->level()?->id,
            level_label: $user->level()?->label,
            is_active: $user->is_active,
            must_change_password: $user->must_change_password,
            last_login_at: $user->last_login_at?->toIso8601String(),
            created_at: $user->created_at?->toIso8601String() ?? '',
            lock_version: $user->lock_version,
            is_self: $viewer instanceof Model && $viewer->is($user),
            extra: UserExtension::values($user),
        );
    }
}
