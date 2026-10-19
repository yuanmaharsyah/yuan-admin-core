<?php

declare(strict_types=1);

namespace Yuan\AdminCore;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Yuan\AdminCore\Console\DoctorCommand;
use Yuan\AdminCore\Console\ModulesCacheCommand;
use Yuan\AdminCore\Console\ModulesClearCommand;
use Yuan\AdminCore\Console\ModulesListCommand;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;
use Yuan\AdminCore\Registry\PageResolver;

final class AdminCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin-core.php', 'admin-core');

        $this->app->singleton(ModuleRegistry::class, fn (): ModuleRegistry => ModuleRegistry::fromConfig(config()));
        $this->app->singleton(PageResolver::class);

        foreach ($this->app->make(ModuleRegistry::class)->enabled() as $module) {
            if (class_exists($module->providerClass())) {
                $this->app->register($module->providerClass());
            }
        }
    }

    public function boot(ModuleRegistry $registry): void
    {
        foreach ($registry->all() as $module) {
            $this->loadMigrationsFrom($module->path('Database/Migrations'));
        }

        if (! $this->app->routesAreCached()) {
            foreach ($registry->enabled() as $module) {
                $this->registerRoutes($module);
            }
        }

        // Denies everyone until RBAC's Gate::before lets Super Admin through.
        Gate::define('admin-core.superadmin', fn (): bool => false);

        $this->app->make(PageResolver::class)->register($this->app);

        $this->publishes([__DIR__.'/../config/admin-core.php' => $this->app->configPath('admin-core.php')], 'admin-core-config');

        if ($this->app->runningInConsole()) {
            $this->commands([ModulesCacheCommand::class, ModulesClearCommand::class, ModulesListCommand::class, DoctorCommand::class]);
            $this->optimizes(optimize: 'modules:cache', clear: 'modules:clear', key: 'modules');
        }
    }

    private function registerRoutes(ModuleDefinition $module): void
    {
        $routes = $module->path('Routes/web.php');

        if (! is_file($routes)) {
            return;
        }

        /** @var list<string> $middleware */
        $middleware = config()->array('admin-core.routes.middleware');

        if ($module->superadminOnly) {
            $middleware[] = 'can:admin-core.superadmin';
        }

        Route::middleware($middleware)
            ->prefix(trim(config()->string('admin-core.routes.prefix'), '/').'/'.$module->slug)
            ->name($module->slug.'.')
            ->group($routes);
    }
}
