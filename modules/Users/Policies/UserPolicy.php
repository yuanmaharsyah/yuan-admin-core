<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Yuan\AdminCore\Modules\Users\Models\User;

/**
 * Users (spec §6.2). Super Admin accounts never reach these checks for other
 * users: `visibleTo()` already turned them into a 404. The own-account rule
 * (§6.3) lives in `UserAccounts::refuseSelf()`: Gate::before would let a
 * Super Admin past a policy check.
 */
final class UserPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $user->can('users.view');
    }

    public function view(Authorizable $user, User $target): bool
    {
        return $user->can('users.view');
    }

    public function create(Authorizable $user): bool
    {
        return $user->can('users.create');
    }

    public function update(Authorizable $user, User $target): bool
    {
        return $user->can('users.edit');
    }

    public function delete(Authorizable $user, User $target): bool
    {
        return $user->can('users.delete');
    }

    public function download(Authorizable $user): bool
    {
        return $user->can('users.download');
    }
}
