<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Maatwebsite\Excel\Facades\Excel;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditCategory;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterData;
use Yuan\AdminCore\Modules\AuditLog\Exports\AuditLogExport;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditQuery;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditSummary;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\travelTo;

// Log Aktivitas (frontend-behavior §8.3, spec §7.1): read-only, every read
// under the SA visibility rule, summary deferred and cached.

uses(RefreshDatabase::class);

function auditStaff(string $name = 'Siti Rahmawati'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->assignLevel(Level::query()->firstOrCreate(['name' => 'staf'], ['label' => 'Staf']));

    return $user->refresh();
}

function auditSuperAdmin(): User
{
    $user = User::factory()->create(['name' => 'Admin Utama']);
    $user->assignLevel(Level::superAdmin());

    return $user->refresh();
}

/**
 * Rows caused by and about a Super Admin, and rows of a staff user; returns
 * the staff user. Only the staff rows exist for a non-SA viewer.
 */
function auditRows(): User
{
    $staff = auditStaff();
    $admin = auditSuperAdmin();
    Audit::actingAs($admin, fn () => Audit::record(AuditEvent::Login));
    Audit::actingAs($admin, fn () => Audit::record(AuditEvent::PasswordReset, $staff, ['by_admin' => true], 'users'));
    Audit::actingAs($staff, fn () => Audit::record(AuditEvent::Login));
    Audit::actingAs($staff, fn () => Audit::record(AuditEvent::LoginFailed, $admin, ['username' => 'admin.utama']));
    Audit::actingAs($staff, fn () => Audit::record(AuditEvent::Exported, meta: ['rows' => 1250, 'format' => 'xlsx'], module: 'users'));

    return $staff;
}

/**
 * @return Collection<array-key, mixed>
 */
function listedEvents(AssertableInertia $page): Collection
{
    return collect(Arr::array($page->toArray(), 'props.events.items'));
}

it('redirects guests to login', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/audit-log');
    ModuleAssertions::assertGuestRedirected('get', '/admin/audit-log/download');
});

it('needs audit-log.view for the page and audit-log.download for the file', function (): void {
    ModuleAssertions::assertForbiddenWithout('audit-log.view', 'get', '/admin/audit-log');
    ModuleAssertions::assertForbiddenWithout('audit-log.download', 'get', '/admin/audit-log/download');
});

it('lists the events first and sends the summary deferred', function (): void {
    auditRows();
    ModuleAssertions::actingAsSuperAdmin();

    $page = AssertableInertia::fromTestResponse(get('/admin/audit-log')->assertOk())
        ->component('AuditLog::Index')
        ->missing('summary')
        ->where('filters.range', '30d')
        ->where('table.filters.0.key', 'event');

    // The Super Admin who signs in here adds one login row of its own.
    expect(listedEvents($page)->pluck('event')->all())->toContain('login', 'password_reset', 'login_failed', 'exported');

    $page->loadDeferredProps(fn (AssertableInertia $reload) => $reload
        ->where('summary.bucket', 'day')
        ->has('summary.by_day', 30)
        ->where('summary.failed_logins', 1)
        ->where('summary.downloads', 1)
        ->where('summary.rows_downloaded', 1250)
        ->where('summary.by_type.3.key', 'auth'));
});

it('never shows a non-Super Admin an event caused by or about a Super Admin', function (): void {
    $staff = auditRows();
    ModuleAssertions::actingAsLevelWith(['audit-log.view', 'audit-log.download']);
    $hidden = ImmutableActivity::query()->whereIn('event', ['password_reset', 'login_failed'])->pluck('id');
    $own = ImmutableActivity::query()->where('causer_id', $staff->id)->where('event', 'login')->value('id');

    $page = AssertableInertia::fromTestResponse(get('/admin/audit-log'));
    expect(listedEvents($page)->pluck('id')->intersect($hidden))->toBeEmpty()
        ->and(listedEvents($page)->pluck('causer_name')->filter()->unique()->values()->all())->not->toContain('Admin Utama')
        ->and($page->toArray()['props']['filters']['users'])->not->toContain(['value' => (string) User::query()->where('name', 'Admin Utama')->value('id'), 'label' => 'Admin Utama']);

    $page->loadDeferredProps(fn (AssertableInertia $reload) => $reload
        ->where('summary.failed_logins', 0)
        ->where('summary.by_type.3.value', 1));

    AssertableInertia::fromTestResponse(get("/admin/audit-log?event_id={$hidden->first()}"))->where('event', null);
    AssertableInertia::fromTestResponse(get("/admin/audit-log?event_id={$own}"))->where('event.item.id', $own);

    Excel::fake();
    get('/admin/audit-log/download')->assertOk();
    Excel::assertDownloaded('audit-log-'.now()->format('Ymd-His').'.xlsx', fn (AuditLogExport $export): bool => $export->query()->whereIn('id', $hidden)->doesntExist());
});

it('filters the list by user, module, event group, batch and search', function (): void {
    $staff = auditRows();
    $other = auditStaff('Rudi Hartono');
    Audit::actingAs($other, fn () => Audit::batch(function () use ($other): void {
        Audit::record(AuditEvent::LevelChanged, $other, module: 'users');
        Audit::record(AuditEvent::PasswordReset, $other, module: 'users');
    }));
    $batch = ImmutableActivity::query()->latest('id')->value('batch_uuid');
    ModuleAssertions::actingAsSuperAdmin();

    $ids = fn (string $query): array => listedEvents(AssertableInertia::fromTestResponse(get("/admin/audit-log?{$query}")))->pluck('event')->sort()->values()->all();

    expect($ids("filter[user]={$staff->id}"))->toBe(['exported', 'login', 'login_failed'])
        ->and($ids('filter[module]=users'))->toContain('exported', 'level_changed', 'password_reset')
        ->and($ids('filter[module]=users'))->not->toContain('login')
        ->and($ids("filter[user]={$staff->id}&filter[event]=auth"))->toBe(['login', 'login_failed'])
        ->and($ids("filter[user]={$staff->id}&filter[event]=other"))->toBe(['exported'])
        ->and($ids("filter[batch]={$batch}"))->toBe(['level_changed', 'password_reset'])
        ->and(listedEvents(AssertableInertia::fromTestResponse(get('/admin/audit-log?search=rudi')))->pluck('subject_label')->unique()->all())->toBe(['Rudi Hartono']);

    AssertableInertia::fromTestResponse(get("/admin/audit-log?filter[batch]={$batch}"))
        ->where('events.items.0.batch_size', 2)
        ->where('table.filters.1.key', 'batch');
});

it('caps a custom range at 366 days and buckets ranges over 60 days by week', function (): void {
    ModuleAssertions::actingAsSuperAdmin();

    getJson('/admin/audit-log?range=custom&from=2025-01-01&to=2026-01-02')->assertUnprocessable()->assertJsonValidationErrors('to');
    getJson('/admin/audit-log?range=custom&from=2026-02-01&to=2026-01-01')->assertJsonValidationErrors('to');
    getJson('/admin/audit-log?range=1y')->assertJsonValidationErrors('range');
    getJson('/admin/audit-log?filter[event]=nope')->assertJsonValidationErrors('filter.event');

    AssertableInertia::fromTestResponse(get('/admin/audit-log?range=custom&from=2025-01-01&to=2026-01-01')->assertOk())
        ->loadDeferredProps(fn (AssertableInertia $reload) => $reload->where('summary.bucket', 'week')->where('summary.from', '2025-01-01'));
    AssertableInertia::fromTestResponse(get('/admin/audit-log?range=90d'))
        ->loadDeferredProps(fn (AssertableInertia $reload) => $reload->where('summary.bucket', 'week'));
    AssertableInertia::fromTestResponse(get('/admin/audit-log?range=7d'))
        ->loadDeferredProps(fn (AssertableInertia $reload) => $reload->where('summary.bucket', 'day')->has('summary.by_day', 7));
});

it('caches the summary per range, user, module and viewer kind for 5 minutes', function (): void {
    $staff = auditRows();
    $viewer = auditStaff('Pengamat');
    $summary = app(AuditSummary::class);
    $filters = AuditFilterData::from([]);

    $first = $summary->for($filters, $viewer);
    Audit::actingAs($staff, fn () => Audit::record(AuditEvent::Login));

    expect($summary->for($filters, auditStaff('Pengamat Lain'))->total)->toBe($first->total)
        ->and($summary->for($filters, auditSuperAdmin())->total)->toBeGreaterThan($first->total)
        ->and($summary->for(AuditFilterData::from(['filter' => ['module' => 'auth']]), $viewer)->total)->toBe(2);

    travelTo(now()->addMinutes(6));
    expect($summary->for($filters, $viewer)->total)->toBeGreaterThan($first->total);
    Cache::flush();
});

it('subtracts the Super Admin rows to the same summary as the direct visibleTo() query', function (): void {
    $admin = auditSuperAdmin();
    $other = auditSuperAdmin();
    $staff = auditStaff();
    $quiet = auditStaff('Hanya Gagal');
    $user = $staff->getMorphClass();
    $row = fn (string $log, ?string $event, ?string $causerType, ?int $causer, ?int $subject, int $daysAgo, int $rows = 0): array => [
        'log_name' => $log, 'description' => 'test', 'event' => $event,
        'causer_type' => $causerType, 'causer_id' => $causer,
        'subject_type' => $subject === null ? null : $user, 'subject_id' => $subject,
        'properties' => json_encode(['meta' => $rows > 0 ? ['rows' => $rows] : []]),
        'created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo),
    ];
    DB::table('activity_log')->insert([
        $row('levels', 'permissions_updated', $user, $admin->id, $other->id, 1),   // SA on both sides: hidden once
        $row('auth', 'login', $user, $admin->id, null, 2),
        $row('auth', 'login_failed', $user, $quiet->id, $admin->id, 3),             // a staff row about a SA
        $row('users', 'exported', $user, $staff->id, null, 3, 100),
        $row('users', 'exported', $user, $staff->id, $other->id, 4, 40),
        $row('users', 'exported', $user, $admin->id, null, 4, 7),
        $row('system', null, 'system', $admin->id, null, 5),                        // not a user causer: visible
        $row('users', 'updated', null, null, $admin->id, 6),
        $row('invoices', 'created', null, null, null, 6),
        $row('auth', 'login', $user, $staff->id, $staff->id, 7),
        $row('auth', 'login', $user, $staff->id, null, 40),                         // outside the range
    ]);
    $viewer = auditStaff('Pengamat');
    $summary = app(AuditSummary::class);

    foreach ([[], ['filter' => ['module' => 'users']], ['filter' => ['user' => $staff->id]], ['filter' => ['user' => $admin->id]], ['range' => '7d']] as $input) {
        $filters = AuditFilterData::from($input);
        $rows = AuditQuery::scope($filters, $viewer)->get();
        $count = fn (AuditEvent $event): int => $rows->where('event', $event->value)->count();
        Cache::flush();
        $data = $summary->for($filters, $viewer);

        expect($data->total)->toBe($rows->count())
            ->and($data->active_users)->toBe($rows->where('causer_type', $user)->unique('causer_id')->count())
            ->and($data->failed_logins)->toBe($count(AuditEvent::LoginFailed))
            ->and($data->downloads)->toBe($count(AuditEvent::Exported))
            ->and($data->rows_downloaded)->toBe((int) $rows->where('event', 'exported')->sum(fn (ImmutableActivity $activity): int => (int) ($activity->properties?->get('meta')['rows'] ?? 0)))
            ->and($data->permission_changes)->toBe($count(AuditEvent::PermissionsUpdated))
            ->and($data->logins)->toBe($count(AuditEvent::Login))
            ->and(collect($data->by_type)->pluck('value', 'key')->all())->toBe(collect(AuditCategory::cases())->mapWithKeys(fn (AuditCategory $category): array => [$category->value => $rows->filter(fn (ImmutableActivity $activity): bool => AuditCategory::of((string) $activity->event) === $category)->count()])->all())
            ->and(collect($data->by_day)->pluck('value', 'key')->filter()->all())->toBe($rows->countBy(fn (ImmutableActivity $activity): string => $activity->created_at?->toDateString() ?? '')->sortKeys()->all());
    }

    // Of the ten rows in range, four stay visible (exported 100, system, invoices, the staff login).
    expect(AuditQuery::scope(AuditFilterData::from([]), $viewer)->where('description', 'test')->count())->toBe(4);
    Cache::flush();
});

it('opens the drawer from the URL with the context, the diff and the batch link', function (): void {
    $staff = auditStaff();
    ModuleAssertions::actingAsSuperAdmin();
    $staff->update(['name' => 'Siti R.']);
    $row = ImmutableActivity::query()->where('event', 'updated')->latest('id')->firstOrFail();

    AssertableInertia::fromTestResponse(get("/admin/audit-log?event_id={$row->id}"))
        ->where('event.item.event_label', 'Diubah')
        ->where('event.item.subject_label', 'Siti R.')
        ->where('event.diff.0.field', 'Name')
        ->where('event.diff.0.old', 'Siti Rahmawati')
        ->where('event.diff.0.new', 'Siti R.')
        ->where('event.subject_href', route('users.show', $staff))
        ->where('event.context.0.label', 'User');

    AssertableInertia::fromTestResponse(get('/admin/audit-log?event_id=999999'))->where('event', null);
});

it('downloads the list with its filters and writes one exported row', function (): void {
    Excel::fake();
    $staff = auditRows();
    ModuleAssertions::actingAsLevelWith(['audit-log.view', 'audit-log.download']);

    get("/admin/audit-log/download?filter[user]={$staff->id}&range=7d")->assertOk();

    Excel::assertDownloaded('audit-log-'.now()->format('Ymd-His').'.xlsx', fn (AuditLogExport $export): bool => $export->query()->count() === 2
        && $export->headings()[0] === 'Waktu');
    expect(ImmutableActivity::query()->where('event', 'exported')->where('log_name', 'audit-log')->count())->toBe(1);
});

it('prunes rows past the retention only when one is set, leaving one audit_pruned row', function (): void {
    travelTo(now()->subDays(40));
    auditRows();
    travelTo(now()->addDays(40));
    Audit::actingAs(auditStaff('Baru'), fn () => Audit::record(AuditEvent::Login));
    $total = ImmutableActivity::query()->count();
    $recent = ImmutableActivity::query()->where('created_at', '>=', now()->subDays(30))->count();

    expect(Artisan::call('admin:audit-prune'))->toBe(0);
    expect(ImmutableActivity::query()->count())->toBe($total);

    config(['admin-core.audit.retention_days' => 30]);
    expect(Artisan::call('admin:audit-prune'))->toBe(0);

    $pruned = ImmutableActivity::query()->where('event', 'audit_pruned')->sole();
    expect(ImmutableActivity::query()->where('created_at', '<', now()->subDays(30))->count())->toBe(0)
        ->and($pruned->log_name)->toBe('system')
        ->and($pruned->properties?->get('meta'))->toMatchArray(['retention_days' => 30])
        ->and($pruned->properties?->get('meta')['rows'] ?? null)->toBe($total - $recent)
        ->and(ImmutableActivity::query()->count())->toBe($recent + 1);
});
