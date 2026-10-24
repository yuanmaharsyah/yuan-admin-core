<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Log Aktivitas (spec §7.1): read-only. Which rows a viewer sees is
 * `ImmutableActivity::visibleTo()`, not this policy.
 */
final class ActivityPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $user->can('audit-log.view');
    }

    public function download(Authorizable $user): bool
    {
        return $user->can('audit-log.download');
    }
}
