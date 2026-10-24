<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests;

use Illuminate\Contracts\Config\Repository;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;
use Yuan\AdminCore\AdminCoreServiceProvider;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

#[WithMigration]
abstract class TestCase extends Orchestra
{
    public const string FIXTURES = __DIR__.'/Fixtures';

    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, LaravelDataServiceProvider::class, ActivitylogServiceProvider::class, PermissionServiceProvider::class, QueryBuilderServiceProvider::class, AdminCoreServiceProvider::class];
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
            $config->set('admin-core.pages', ['overrides' => [], 'core_path' => self::FIXTURES.'/CorePages']);
            $config->set('auth.providers.users.model', User::class);
            // Production's time zone (Testbench's config pins UTC), so day boundaries are tested as they run.
            $config->set('app.timezone', 'Asia/Jakarta');
            date_default_timezone_set('Asia/Jakarta');
            // Like a real store: cached values are serialized, and no object comes back.
            $config->set('cache.stores.array.serialize', true);
            $config->set('cache.serializable_classes', false);
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
