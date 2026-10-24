<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Dashboard\DashboardRange;

use function Pest\Laravel\travelTo;

// Days are the app's days (Asia/Jakarta in production and in this suite):
// an event at 23:30 WIB belongs to that day, not to the next UTC one.

uses(RefreshDatabase::class);

it('runs in the production time zone', function (): void {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(date_default_timezone_get())->toBe('Asia/Jakarta');
});

it('stores an event at 23:30 WIB on that day', function (): void {
    travelTo(CarbonImmutable::parse('2026-10-24 23:30:00', 'Asia/Jakarta'));

    Audit::record(AuditEvent::Login);

    $row = DB::table('activity_log')->where('event', 'login')->sole(['created_at', 'created_on']);
    expect($row->created_at)->toBe('2026-10-24 23:30:00')
        ->and($row->created_on)->toBe('2026-10-24');
});

it('ends the Beranda range on the local day, and moves it at local midnight', function (): void {
    travelTo(CarbonImmutable::parse('2026-10-24 23:30:00', 'Asia/Jakarta'));
    $late = new DashboardRange('7d');
    travelTo(CarbonImmutable::parse('2026-10-25 00:10:00', 'Asia/Jakarta'));
    $early = new DashboardRange('7d');

    expect([$late->from->toDateString(), $late->to->toDateString()])->toBe(['2026-10-18', '2026-10-24'])
        ->and([$early->from->toDateString(), $early->to->toDateString()])->toBe(['2026-10-19', '2026-10-25']);
});
