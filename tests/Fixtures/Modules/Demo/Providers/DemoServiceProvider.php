<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Providers;

use Illuminate\Support\ServiceProvider;

final class DemoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('fixtures.demo.registered', true);
    }
}
