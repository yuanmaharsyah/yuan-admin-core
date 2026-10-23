<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/** An admin deactivated a user account (spec §2.2); dispatched after the commit. */
final class UserDeactivated
{
    use Dispatchable;

    public function __construct(
        public readonly Model $user,
    ) {}
}
