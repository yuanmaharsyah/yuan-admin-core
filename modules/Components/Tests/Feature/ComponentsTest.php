<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\get;

// Komponen (DEVELOPER.md R21): the house-style catalogue, Super Admin only and never grantable.

uses(RefreshDatabase::class);

it('redirects guests to login', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/components');
});

it('refuses every other level, even one holding every permission there is', function (): void {
    ModuleAssertions::actingAsLevelWith(['users.view', 'audit-log.view', 'dashboard.view']);

    get('/admin/components')->assertForbidden();
});

it('shows Super Admin the catalogue with the sample DataTable page', function (): void {
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get('/admin/components')->assertOk())
        ->component('Components::Index')
        ->has('table.items', 6)
        ->where('table.items.0.name', 'Toko Sinar Jaya')
        ->where('state.searchable', false);
});
