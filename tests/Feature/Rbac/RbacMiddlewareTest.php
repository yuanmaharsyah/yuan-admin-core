<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Http\Middleware\HandleInertiaRequests;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    syncPermissions();
    Route::get('/login', fn () => 'login')->name('login');
    Route::middleware(['web', 'auth', 'active', 'permission:demo.create'])->get('/admin/demo/create', fn () => 'create');
    Route::middleware(['web', HandleInertiaRequests::class])->get('/probe', fn () => Inertia::render('Demo::Index'));
});

it('checks permission:{slug}.{action}', function (): void {
    $viewer = makeUser('Viewer');
    $viewer->assignLevel(levelWith(['demo.view'], 'viewer'));
    $editor = makeUser('Editor');
    $editor->assignLevel(levelWith(['demo.view', 'demo.create'], 'editor'));

    actingAs($viewer)->get('/admin/demo/create')->assertForbidden();
    actingAs($editor)->get('/admin/demo/create')->assertOk();
    actingAs(superAdminUser())->get('/admin/demo/create')->assertOk();
});

it('lets only Super Admin into superadmin_only areas', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith(['demo.view', 'notes.view', 'hidden.view']));

    actingAs($user)->get('/admin/admin-only')->assertForbidden();
    actingAs(superAdminUser())->get('/admin/admin-only')->assertOk();
});

it('sends guests to login', function (): void {
    get('/admin/demo')->assertRedirect('/login');
});

it('logs a deactivated user out of an existing session, with an error flash', function (): void {
    $user = makeUser();
    $user->assignLevel(levelWith(['demo.view']));
    actingAs($user)->get('/admin/demo')->assertOk();

    DB::table('users')->where('id', $user->id)->update(['is_active' => false]);

    actingAs($user->fresh() ?? $user)->get('/admin/demo')->assertRedirect('/login');
    assertGuest();
    expect(session('inertia.flash_data'))->toMatchArray(['error' => 'Sesi Anda telah berakhir. Silakan masuk kembali.']);
});

it('shares the level and the permissions', function (): void {
    $user = makeUser();
    $user->assignLevel($level = levelWith(['demo.view', 'notes.view']));

    AssertableInertia::fromTestResponse(actingAs($user)->get('/probe'))
        ->where('auth.level', ['id' => $level->id, 'name' => 'staf', 'label' => 'Staf'])
        ->where('auth.permissions', ['demo.view', 'notes.view'])
        ->where('auth.is_super_admin', false);

    AssertableInertia::fromTestResponse(actingAs(superAdminUser())->get('/probe'))
        ->where('auth.level.name', 'super-admin')
        ->where('auth.permissions', ['demo.approve', 'demo.create', 'demo.view', 'hidden.view', 'notes.view'])
        ->where('auth.is_super_admin', true);
});
