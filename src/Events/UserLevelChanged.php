<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Yuan\AdminCore\Permissions\Level;

/** A user's level was replaced (spec §2.2); dispatched after the commit. */
final class UserLevelChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly ?Level $from,
        public readonly Level $to,
    ) {}
}
