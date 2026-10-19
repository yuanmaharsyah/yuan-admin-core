<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

it('prefixes, names and protects module routes', function (): void {
    $route = Route::getRoutes()->getByName('demo.index');

    expect($route)->toBeInstanceOf(RouteDefinition::class)
        ->and($route?->uri())->toBe('admin/demo')
        ->and($route?->gatherMiddleware())->toBe(['web', 'auth'])
        ->and(route('notes.index'))->toBe(url('/admin/notes'));
});

it('adds the Super Admin ability to superadmin_only routes', function (): void {
    expect(Route::getRoutes()->getByName('admin-only.index')?->gatherMiddleware())
        ->toBe(['web', 'auth', 'can:admin-core.superadmin']);
});

it('denies superadmin_only routes to every user until RBAC exists', function (): void {
    actingAs(new GenericUser(['id' => 1]))
        ->get('/admin/admin-only')
        ->assertForbidden();
});

it('registers no routes for disabled modules', function (): void {
    expect(Route::has('off.index'))->toBeFalse()
        ->and(Route::has('hidden.index'))->toBeTrue();
});

it('uses only registered middleware on every route', function (): void {
    app(Kernel::class); // the HTTP kernel hands its middleware groups to the router
    $router = app(Router::class);
    $known = [...array_keys($router->getMiddleware()), ...array_keys($router->getMiddlewareGroups())];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        foreach ($route->gatherMiddleware() as $middleware) {
            $name = is_string($middleware) ? strtok($middleware, ':') : $middleware::class;

            expect(in_array($name, $known, true) || class_exists($name))
                ->toBeTrue("Route [{$route->uri()}] uses unregistered middleware [{$name}].");
        }
    }
});
