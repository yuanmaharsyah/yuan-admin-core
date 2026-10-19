<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Spatie\LaravelData\Data;
use Yuan\AdminCore\Http\Middleware\HandleInertiaRequests;
use Yuan\AdminCore\Tests\Fixtures\Http\ProjectInertiaMiddleware;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withCookie;

beforeEach(function (): void {
    Route::middleware(['web', HandleInertiaRequests::class])->get('/probe', fn () => Inertia::render('Demo::Index'));
    Route::middleware(['web', ProjectInertiaMiddleware::class])->get('/project-probe', fn () => Inertia::render('Demo::Index'));
});

function user(): GenericUser
{
    return new GenericUser(['id' => 7, 'name' => 'Budi', 'email' => 'budi@example.test', 'password' => 'secret', 'remember_token' => 'token']);
}

it('shares guest props', function (): void {
    AssertableInertia::fromTestResponse(get('/probe'))
        ->where('auth', ['user' => null, 'is_super_admin' => false])
        ->where('menu', [])
        ->where('app', ['name' => config('app.name'), 'locale' => app()->getLocale(), 'timezone' => config('app.timezone'), 'panel_open' => true]);
});

it('shares only id, name and email of the user, and their menu', function (): void {
    Gate::define('demo.view', fn (): bool => true);

    AssertableInertia::fromTestResponse(actingAs(user())->get('/probe'))
        ->where('auth.user', ['id' => 7, 'name' => 'Budi', 'email' => 'budi@example.test'])
        ->where('auth.is_super_admin', false)
        ->where('menu.0.key', 'master')
        ->where('menu.0.items.0.slug', 'demo')
        ->count('menu', 1);
});

it('flags Super Admin', function (): void {
    Gate::before(fn (): bool => true);

    AssertableInertia::fromTestResponse(actingAs(user())->get('/probe'))->where('auth.is_super_admin', true);
});

it('reads the panel state from the admin_panel cookie', function (): void {
    AssertableInertia::fromTestResponse(withCookie('admin_panel', 'false')->get('/probe'))->where('app.panel_open', false);
});

it('shares only Data objects', function (): void {
    Gate::define('demo.view', fn (): bool => true);
    $request = Request::create('/probe')->setUserResolver(fn (): GenericUser => user());
    $shared = (new HandleInertiaRequests)->share($request);

    expect($shared['auth']())->toBeInstanceOf(Data::class)
        ->and($shared['app']())->toBeInstanceOf(Data::class)
        ->and($shared['menu']())->not->toBeEmpty()->toContainOnlyInstancesOf(Data::class);
});

it('lets a project subclass add props and keeps every core prop', function (): void {
    AssertableInertia::fromTestResponse(get('/project-probe'))
        ->has('auth')
        ->has('menu')
        ->has('app')
        ->where('project.name', 'Project');
});
