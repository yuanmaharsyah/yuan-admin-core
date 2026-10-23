<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Modules\Users\Models\User;

#[TypeScript]
final class UserListItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $username,
        public string $email,
        public ?string $level_label,
        public bool $is_active,
        public ?string $last_login_at,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            username: $user->username,
            email: $user->email,
            level_label: $user->level()?->label,
            is_active: $user->is_active,
            last_login_at: $user->last_login_at?->toIso8601String(),
        );
    }
}
