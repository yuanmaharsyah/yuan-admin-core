<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests;

use Illuminate\Routing\Router;

/**
 * Boots only the compliant fixture root (`Fixtures/Contract`), for the
 * contract assertions and the fixture modules' own tests.
 */
abstract class ContractTestCase extends TestCase
{
    /** @var array{name: string, path: string, namespace: string} */
    public const array CONTRACT_ROOT = ['name' => 'project', 'path' => self::FIXTURES.'/Contract', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Contract\\'];

    /** @var array{name: string, path: string, namespace: string} */
    public const array BROKEN_ROOT = ['name' => 'project', 'path' => self::FIXTURES.'/Broken', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Broken\\'];

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('admin-core.modules.roots', [self::CONTRACT_ROOT]);
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->get('/login', fn (): string => 'login')->name('login');
    }
}
