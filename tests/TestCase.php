<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests;

use Illuminate\Contracts\Config\Repository;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Yuan\AdminCore\AdminCoreServiceProvider;

abstract class TestCase extends Orchestra
{
    public const string FIXTURES = __DIR__.'/Fixtures';

    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, LaravelDataServiceProvider::class, AdminCoreServiceProvider::class];
    }

    /**
     * Module config must be in place before the package provider registers,
     * which happens before defineEnvironment().
     */
    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        tap($app['config'], function (Repository $config): void {
            $config->set('admin-core.modules', [
                'roots' => fixtureRoots(),
                'disabled' => [],
                'cache_path' => sys_get_temp_dir().'/admin-core-test-modules-'.getmypid().'.php',
            ]);
            $config->set('view.paths', [self::FIXTURES.'/views']);
        });
    }

    protected function tearDown(): void
    {
        $cache = sys_get_temp_dir().'/admin-core-test-modules-'.getmypid().'.php';

        if (is_file($cache)) {
            unlink($cache);
        }

        parent::tearDown();
    }
}
