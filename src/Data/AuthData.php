<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Permission;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Permissions\Level;

/**
 * The signed-in user as the frontend sees it (frontend-behavior §2). Super
 * Admin gets every synced permission, so `can()` needs no SA special case;
 * `is_super_admin` is for superadmin_only areas.
 */
#[TypeScript]
final class AuthData extends Data
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public ?UserData $user,
        public ?LevelData $level,
        public bool $is_super_admin,
        public array $permissions,
    ) {}

    public static function fromUser(?Authenticatable $user): self
    {
        if ($user === null) {
            return new self(user: null, level: null, is_super_admin: false, permissions: []);
        }

        $isSuperAdmin = Gate::forUser($user)->allows('admin-core.superadmin');
        $level = method_exists($user, 'level') ? $user->level() : null;
        $permissions = match (true) {
            $isSuperAdmin => Permission::query()->orderBy('name')->pluck('name')->all(),
            method_exists($user, 'getAllPermissions') => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
            default => [],
        };

        return new self(
            user: UserData::fromUser($user),
            level: $level instanceof Level ? LevelData::fromLevel($level) : null,
            is_super_admin: $isSuperAdmin,
            permissions: array_values(array_map(strval(...), $permissions)),
        );
    }
}
