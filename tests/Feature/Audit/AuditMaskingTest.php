<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

/**
 * Both JSON columns of the latest row, as stored.
 */
function storedColumns(): string
{
    $row = DB::table('activity_log')->latest('id')->first(['attribute_changes', 'properties']);

    return ($row->attribute_changes ?? '').($row->properties ?? '');
}

it('masks model changes in attribute_changes: omit absent, partial and full masked', function (): void {
    $item = DemoItem::query()->create(['name' => 'A', 'nik' => '3201234567890001', 'no_hp' => '081234567890', 'secret' => 'kunci-rahasia']);

    expect(latestActivity()->attribute_changes?->get('attributes'))->toBe([
        'name' => 'A', 'nik' => '3201••••0001', 'no_hp' => Masker::HIDDEN,
    ]);

    $item->update(['nik' => '3201234567890002', 'secret' => 'kunci-baru']);

    expect(latestActivity()->attribute_changes?->all())->toBe([
        'attributes' => ['nik' => '3201••••0002'],
        'old' => ['nik' => '3201••••0001'],
    ])->and(storedColumns())->not->toMatch('/3201234567890|kunci|081234567890/');
});

it('masks meta in properties, nested keys and wildcard patterns included', function (): void {
    Audit::record(AuditEvent::SettingsUpdated, meta: [
        'password' => 'Rahasia123',
        'api_token' => 'tok-abc',
        'nik' => '3201234567890001',
        'warga' => ['alamat' => 'Jl. Merdeka 1', 'no_kk' => '3201987654320001'],
        'keys' => ['app.name'],
    ]);

    expect(latestActivity()->getProperty('meta'))->toBe([
        'nik' => '3201••••0001',
        'warga' => ['alamat' => Masker::HIDDEN, 'no_kk' => '3201••••0001'],
        'keys' => ['app.name'],
    ])->and(storedColumns())->not->toMatch('/Rahasia123|tok-abc|Merdeka/');
});

it('hides a partial value of 8 characters or fewer entirely', function (): void {
    Audit::record(AuditEvent::SettingsUpdated, meta: ['npwp' => '12345678', 'no_rekening' => null]);

    expect(latestActivity()->getProperty('meta'))->toBe(['npwp' => Masker::HIDDEN, 'no_rekening' => null]);
});
