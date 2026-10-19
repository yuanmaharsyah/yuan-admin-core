<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Modules\Off\Providers;

use Illuminate\Support\ServiceProvider;

final class OffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('fixtures.off.registered', true);
    }
}
