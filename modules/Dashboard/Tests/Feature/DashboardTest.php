<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\WidgetProbe;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\get;

// Beranda (spec §7.2, frontend-behavior §8.6): the permitted widgets only,
// each a deferred, rescued prop, so one failing widget leaves the others.

uses(RefreshDatabase::class);

it('redirects guests to login', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/dashboard');
});

it('needs dashboard.view', function (): void {
    ModuleAssertions::assertForbiddenWithout('dashboard.view', 'get', '/admin/dashboard');
});

it('lists the permitted widgets in order and sends each one deferred', function (): void {
    ModuleAssertions::actingAsLevelWith(['dashboard.view', 'audit-log.view']);

    AssertableInertia::fromTestResponse(get('/admin/dashboard')->assertOk())
        ->component('Dashboard::Index')
        ->where('dashboard.range', '30d')
        ->where('dashboard.widgets.0.key', 'audit-log.failed_logins')
        ->where('dashboard.widgets.0.prop', 'widget_audit_log_failed_logins')
        ->where('dashboard.widgets.1.key', 'audit-log.activity_by_day')
        ->where('dashboard.widgets.1.size', 'full')
        ->missing('widget_audit_log_failed_logins')
        ->loadDeferredProps(fn (AssertableInertia $reload) => $reload
            ->where('widget_audit_log_failed_logins.value', 0)
            ->has('widget_audit_log_activity_by_day.bars', 30));
});

it('sends no widgets to a level without their permissions (the page shows its empty state)', function (): void {
    ModuleAssertions::actingAsLevelWith(['dashboard.view', 'users.view']);

    AssertableInertia::fromTestResponse(get('/admin/dashboard'))->where('dashboard.widgets', []);
});

it('takes the range from the URL, 30 days when it is missing or unknown', function (): void {
    ModuleAssertions::actingAsLevelWith(['dashboard.view', 'audit-log.view']);

    AssertableInertia::fromTestResponse(get('/admin/dashboard?range=7d'))
        ->where('dashboard.range', '7d')
        ->loadDeferredProps(fn (AssertableInertia $reload) => $reload->has('widget_audit_log_activity_by_day.bars', 7));
    AssertableInertia::fromTestResponse(get('/admin/dashboard?range=365d'))->where('dashboard.range', '30d');
});

it('leaves the other widgets loading when one throws, and reports the failure', function (): void {
    Exceptions::fake();
    WidgetProbe::enable();
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get('/admin/dashboard')->assertOk())
        ->reload(fn (AssertableInertia $reload) => $reload
            ->where('widget_widget_probe_steady.value', 30)
            ->missing('widget_widget_probe_broken')
            ->has('widget_audit_log_failed_logins'),
            only: ['widget_widget_probe_steady', 'widget_widget_probe_broken', 'widget_audit_log_failed_logins']);

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'The probe widget fails on purpose.');
});
