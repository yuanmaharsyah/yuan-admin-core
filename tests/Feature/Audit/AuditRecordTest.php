<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Jobs\AuditedJob;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('writes the event, log name, causer, subject, label, meta and context', function (): void {
    $user = makeUser();
    $item = DemoItem::query()->create(['name' => 'Surat 12']);
    actingAs($user);

    Audit::record('approve', $item, ['note' => 'ok']);

    $row = latestActivity();
    expect($row->only(['event', 'log_name', 'description', 'causer_id', 'subject_id']))
        ->toBe(['event' => 'approve', 'log_name' => 'demo', 'description' => 'approve', 'causer_id' => $user->id, 'subject_id' => $item->id])
        ->and($row->getProperty('meta'))->toBe(['note' => 'ok', 'subject_label' => 'Surat 12'])
        ->and($row->getProperty('context.channel'))->toBe('cli')
        ->and($row->getProperty('context.request_id'))->toBeString()
        ->and($row->batch_uuid)->toBeNull();
});

it('records the web context of a request', function (): void {
    Route::middleware('web')->get('/probe', function () {
        Audit::record(AuditEvent::SettingsUpdated);

        return 'ok';
    })->name('probe');

    get('/probe', ['X-Request-Id' => 'req-123', 'User-Agent' => 'Pest']);

    expect(latestActivity()->getProperty('context'))->toBe([
        'ip' => '127.0.0.1', 'user_agent' => 'Pest', 'route' => 'probe', 'request_id' => 'req-123', 'channel' => 'web',
    ]);
});

it('gives every row of a batch one uuid, and a nested batch joins the outer one', function (): void {
    Audit::batch(function (): void {
        Audit::record(AuditEvent::SettingsUpdated);
        Audit::batch(fn () => DemoItem::query()->create(['name' => 'A']));
    });
    Audit::record(AuditEvent::SettingsUpdated);

    $rows = ImmutableActivity::query()->orderBy('id')->pluck('batch_uuid');
    expect($rows[0])->toBeString()
        ->and($rows[1])->toBe($rows[0])
        ->and($rows[2])->toBeNull()
        ->and(Audit::batchUuid())->toBeNull();
});

it('leaves no activity row when the transaction rolls back', function (): void {
    try {
        DB::transaction(function (): void {
            DemoItem::query()->create(['name' => 'A']);
            Audit::record(AuditEvent::SettingsUpdated);

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(ImmutableActivity::query()->count())->toBe(0)
        ->and(DemoItem::query()->count())->toBe(0);
});

it('gives a queued job the requesting user as causer, with nobody logged in', function (): void {
    config(['queue.default' => 'database']);
    $user = makeUser();

    dispatch(new AuditedJob($user));
    expect(ImmutableActivity::query()->count())->toBe(0);

    Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    $row = latestActivity();
    expect(auth()->user())->toBeNull()
        ->and($row->causer_id)->toBe($user->id)
        ->and($row->getProperty('context.channel'))->toBe('queue')
        ->and($row->getProperty('context.job'))->toBe(AuditedJob::class);
});
