<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Modules\AuditLog\Widgets\ActivityByDay;
use Yuan\AdminCore\Modules\AuditLog\Widgets\FailedLogins;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Testing\ModuleAssertions;

// The two core Beranda widgets (spec §7.2): audit-log.view, the SA
// visibility rule, within the time budget.

uses(RefreshDatabase::class);

it('hides each widget without audit-log.view', function (): void {
    ModuleAssertions::assertWidgetHiddenWithout(FailedLogins::class);
    ModuleAssertions::assertWidgetHiddenWithout(ActivityByDay::class);
});

it('scopes each widget to the viewer through visibleTo()', function (): void {
    ModuleAssertions::actingAsLevelWith(['dashboard.view', 'audit-log.view']);

    ModuleAssertions::assertWidgetUsesScope(FailedLogins::class, ImmutableActivity::class);
    ModuleAssertions::assertWidgetUsesScope(ActivityByDay::class, ImmutableActivity::class);
});

it('counts only the failed logins a non-Super Admin may see', function (): void {
    $admin = User::factory()->create();
    $admin->assignLevel(Level::superAdmin());
    $staff = User::factory()->create();
    Audit::record(AuditEvent::LoginFailed, $admin, ['username' => 'admin']);
    Audit::record(AuditEvent::LoginFailed, $staff, ['username' => 'staf']);
    Audit::record(AuditEvent::LoginFailed, meta: ['username' => 'tidak.ada']);
    $viewer = ModuleAssertions::actingAsLevelWith(['dashboard.view', 'audit-log.view']);

    $data = app(FailedLogins::class)->data($viewer, new DashboardRange('7d'));

    expect($data->value)->toBe(2)
        ->and($data->badge)->toBe('Perlu dicek')
        ->and(app(FailedLogins::class)->data($admin, new DashboardRange('7d'))->value)->toBe(3);
});

it('draws one bar per day of the range, linked to that day on the Log Aktivitas', function (): void {
    $viewer = ModuleAssertions::actingAsLevelWith(['dashboard.view', 'audit-log.view']);
    Audit::record(AuditEvent::Login);

    $data = app(ActivityByDay::class)->data($viewer, new DashboardRange('7d'));
    $today = now()->toDateString();

    expect($data->bars)->toHaveCount(7)
        ->and($data->bars[6]->key)->toBe($today)
        ->and($data->bars[6]->href)->toBe(route('audit-log.index', ['range' => 'custom', 'from' => $today, 'to' => $today]));
});

it('stays within the widget time budget on 20 000 rows', function (): void {
    $rows = [];

    for ($i = 0; $i < 20_000; $i++) {
        $at = now()->subMinutes($i * 6)->toDateTimeString();
        $rows[] = ['log_name' => 'auth', 'description' => 'perf', 'event' => $i % 4 === 0 ? 'login_failed' : 'login', 'properties' => '{}', 'created_at' => $at, 'updated_at' => $at];
    }

    foreach (array_chunk($rows, 2000) as $chunk) {
        DB::table('activity_log')->insert($chunk);
    }

    ModuleAssertions::actingAsLevelWith(['dashboard.view', 'audit-log.view']);

    ModuleAssertions::assertWidgetWithinBudget(FailedLogins::class);
    ModuleAssertions::assertWidgetWithinBudget(ActivityByDay::class);
});
