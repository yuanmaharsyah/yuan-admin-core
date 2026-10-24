<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Modules\AuditLog\Policies\ActivityPolicy;

final class AuditLogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(ImmutableActivity::class, ActivityPolicy::class);
    }
}
