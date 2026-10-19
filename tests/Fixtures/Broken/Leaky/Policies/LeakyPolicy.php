<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Models\Leaky;

/**
 * No approve() for the declared "approve" action.
 */
final class LeakyPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $user->can('leaky.view');
    }

    public function view(Authorizable $user, Leaky $leaky): bool
    {
        return $user->can('leaky.view');
    }
}
