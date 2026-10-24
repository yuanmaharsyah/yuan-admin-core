<?php

declare(strict_types=1);

namespace Yuan\AdminCore;

use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yuan\AdminCore\Audit\AuditContext;
use Yuan\AdminCore\Audit\AuthAuditSubscriber;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\Audit\WriteActivity;
use Yuan\AdminCore\Auth\PasswordRules;
use Yuan\AdminCore\Console\AccessCommand;
use Yuan\AdminCore\Console\AttachmentsSweepCommand;
use Yuan\AdminCore\Console\AuditPruneCommand;
use Yuan\AdminCore\Console\CreateSuperAdminCommand;
use Yuan\AdminCore\Console\DoctorCommand;
use Yuan\AdminCore\Console\ModulesCacheCommand;
use Yuan\AdminCore\Console\ModulesClearCommand;
use Yuan\AdminCore\Console\ModulesListCommand;
use Yuan\AdminCore\Console\PermissionsSyncCommand;
use Yuan\AdminCore\Http\Middleware\EnsureSuperAdmin;
use Yuan\AdminCore\Http\Middleware\EnsureUserIsActive;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;
use Yuan\AdminCore\Registry\PageResolver;
use Yuan\AdminCore\Settings\Settings;

final class AdminCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin-core.php', 'admin-core');

        $this->app->singleton(ModuleRegistry::class, fn (): ModuleRegistry => ModuleRegistry::fromConfig(config()));
        $this->app->singleton(PageResolver::class);
        $this->app->singleton(Settings::class);
        $this->registerAudit();
        config(['permission.models.role' => Level::class]);

        foreach ($this->app->make(ModuleRegistry::class)->enabled() as $module) {
            if (class_exists($module->providerClass())) {
                $this->app->register($module->providerClass());
            }
        }
    }

    public function boot(ModuleRegistry $registry): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        foreach ($registry->all() as $module) {
            $this->loadMigrationsFrom($module->path('Database/Migrations'));
        }

        if (! $this->app->routesAreCached()) {
            foreach ($registry->enabled() as $module) {
                $this->registerRoutes($module);
            }
        }

        // Denies everyone until RBAC's Gate::before lets Super Admin through.
        $this->bootAudit();

        $this->bootRbac();

        $this->app->booted(fn () => $this->app->make(Settings::class)->applyToConfig());

        // Spec §8 password rules; a project's own provider may replace them.
        Password::defaults(fn (): Password => PasswordRules::defaults());

        $this->app->make(PageResolver::class)->register($this->app);

        $this->publishes([__DIR__.'/../config/admin-core.php' => $this->app->configPath('admin-core.php')], 'admin-core-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModulesCacheCommand::class, ModulesClearCommand::class, ModulesListCommand::class, DoctorCommand::class,
                PermissionsSyncCommand::class, CreateSuperAdminCommand::class, AuditPruneCommand::class, AccessCommand::class, AttachmentsSweepCommand::class,
            ]);
            $this->optimizes(optimize: 'modules:cache', clear: 'modules:clear', key: 'modules');
        }
    }

    /**
     * Audit writes are synchronous and go through WriteActivity (spec §7.1),
     * whatever the project's activitylog config says.
     */
    private function registerAudit(): void
    {
        config([
            'activitylog.activity_model' => ImmutableActivity::class,
            'activitylog.actions.log_activity' => WriteActivity::class,
            'activitylog.buffer.enabled' => false,
            'activitylog.default_log_name' => 'system',
        ]);

        $this->app->singleton(Masker::class, function (): Masker {
            /** @var array<string, 'omit'|'partial'|'full'> $rules */
            $rules = config()->array('admin-core.audit.masked_fields');

            return new Masker($rules);
        });
        $this->app->scoped(AuditContext::class);
    }

    private function bootAudit(): void
    {
        Event::subscribe(AuthAuditSubscriber::class);
        Event::listen(JobProcessing::class, fn (JobProcessing $event) => $this->app->make(AuditContext::class)->startJob($event->job->resolveName()));
        Event::listen([JobProcessed::class, JobFailed::class], fn () => $this->app->make(AuditContext::class)->endJob());
        Event::listen(CommandStarting::class, fn (CommandStarting $event) => $this->app->make(AuditContext::class)->startCommand((string) $event->command));
    }

    /**
     * Super Admin passes every gate; this is the only SA special case outside
     * the guard rails (spec §6.2.5).
     */
    private function bootRbac(): void
    {
        Gate::before(fn (mixed $user): ?bool => is_object($user) && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin() ? true : null);
        Gate::define('admin-core.superadmin', fn (): bool => false);

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('active', EnsureUserIsActive::class);
        $router->aliasMiddleware('superadmin', EnsureSuperAdmin::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof Model) {
                return;
            }

            if (request()->hasSession()) {
                request()->session()->put(EnsureUserIsActive::SESSION_VERSION, (int) $event->user->getAttribute('session_version'));
            }

            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
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
            $middleware[] = 'superadmin';
        }

        Route::middleware($middleware)
            ->prefix(trim(config()->string('admin-core.routes.prefix'), '/').'/'.$module->slug)
            ->name($module->slug.'.')
            ->group($routes);
    }
}
