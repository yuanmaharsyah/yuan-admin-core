<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Spatie\LaravelData\Data;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Http\Middleware\HandleInertiaRequests;
use Yuan\AdminCore\Settings\Settings;
use Yuan\AdminCore\Tests\Fixtures\Http\ProjectInertiaMiddleware;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withCookie;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(['web', HandleInertiaRequests::class])->get('/probe', fn () => Inertia::render('Demo::Index'))->name('probe');
    Route::middleware(['web', ProjectInertiaMiddleware::class])->get('/project-probe', fn () => Inertia::render('Demo::Index'));
});

function user(): User
{
    return User::query()->create(['name' => 'Budi', 'email' => 'budi@example.test', 'password' => 'Rahasia123', 'is_active' => true]);
}

it('shares guest props', function (): void {
    AssertableInertia::fromTestResponse(get('/probe'))
        ->where('auth', ['user' => null, 'level' => null, 'is_super_admin' => false, 'permissions' => []])
        ->where('menu', [])
        ->where('app.name', config('app.name'))
        ->where('app.locale', app()->getLocale())
        ->where('app.timezone', config('app.timezone'))
        ->where('app.panel_open', true)
        ->where('app.site_access', null)
        ->where('app.route', 'probe')
        ->where('app.logo', null)
        ->where('app.settings', fn (Collection $settings): bool => $settings->has('kontak.whatsapp') && ! $settings->has('akses.mode'));
});

it('shares only id, name and email of the user, and their menu', function (): void {
    Gate::define('demo.view', fn (): bool => true);

    $user = user();

    AssertableInertia::fromTestResponse(actingAs($user)->get('/probe'))
        ->where('auth.user', ['id' => $user->id, 'name' => 'Budi', 'email' => 'budi@example.test'])
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

it('shares the site access mode for the shell banner while it is not open', function (): void {
    app(Settings::class)->save('akses', ['mode' => 'maintenance', 'pesan' => 'Pemeliharaan Sabtu 22.00–23.00 WIB.']);

    AssertableInertia::fromTestResponse(get('/probe'))->where('app.site_access', ['mode' => 'maintenance', 'message' => 'Pemeliharaan Sabtu 22.00–23.00 WIB.']);

    app(Settings::class)->save('akses', ['mode' => 'open']);

    AssertableInertia::fromTestResponse(get('/probe'))->where('app.site_access', null);
});

it('shares the branding logo from config while Settings has none', function (): void {
    config(['admin-core.branding.logo' => 'images/logo.png']);

    AssertableInertia::fromTestResponse(get('/probe'))->where('app.logo', asset('images/logo.png'));
});

it('renders the breadcrumbs page prop as a list of label and optional link', function (): void {
    Route::middleware(['web', HandleInertiaRequests::class])->get('/crumbs', fn () => Inertia::render('Demo::Index', [
        'breadcrumbs' => BreadcrumbsData::of(new BreadcrumbData('Master', '/admin/demo'), new BreadcrumbData('Demo')),
    ]));

    AssertableInertia::fromTestResponse(get('/crumbs'))->where('breadcrumbs.items', [
        ['label' => 'Master', 'href' => '/admin/demo'],
        ['label' => 'Demo', 'href' => null],
    ]);
});

it('shares only Data objects', function (): void {
    Gate::define('demo.view', fn (): bool => true);
    $user = user();
    $request = Request::create('/probe')->setUserResolver(fn (): User => $user);
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
