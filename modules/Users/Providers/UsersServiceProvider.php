<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Yuan\AdminCore\Modules\Users\Policies\UserPolicy;

/** The policy goes to the project's swapped model, whatever its class. */
final class UsersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(config()->string('admin-core.models.user'), UserPolicy::class);
    }
}
