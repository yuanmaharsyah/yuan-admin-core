<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('prefixes, names and protects module routes', function (): void {
    $route = Route::getRoutes()->getByName('demo.index');

    expect($route)->toBeInstanceOf(RouteDefinition::class)
        ->and($route?->uri())->toBe('admin/demo')
        ->and($route?->gatherMiddleware())->toBe(['web', 'auth', 'active'])
        ->and(route('notes.index'))->toBe(url('/admin/notes'));
});

it('adds the superadmin middleware to superadmin_only routes', function (): void {
    expect(Route::getRoutes()->getByName('admin-only.index')?->gatherMiddleware())
        ->toBe(['web', 'auth', 'active', 'superadmin']);
});

it('denies superadmin_only routes to a user without a level', function (): void {
    actingAs(makeUser())
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
