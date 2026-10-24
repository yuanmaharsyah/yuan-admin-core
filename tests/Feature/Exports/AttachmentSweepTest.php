<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yuan\AdminCore\Audit\ImmutableActivity;

// admin:attachments-sweep: attachment files without a row (a rolled-back
// save, a crash) go after a day; files with a row, and fresh ones, stay.

uses(RefreshDatabase::class);

function storedFile(string $path, int $hoursOld): string
{
    Storage::disk('local')->put($path, 'x');
    touch(Storage::disk('local')->path($path), now()->subHours($hoursOld)->getTimestamp());

    return $path;
}

it('deletes an orphan older than a day, keeps a fresh orphan and a referenced file, and logs one system row', function (): void {
    Storage::fake('local');
    $orphan = storedFile('attachments/invoices/2026/10/orphan.pdf', 25);
    $fresh = storedFile('attachments/invoices/2026/10/fresh.pdf', 2);
    $referenced = storedFile('attachments/invoices/2026/10/kept.pdf', 72);
    $export = storedFile('exports/old-download.xlsx', 72);
    DB::table('attachments')->insert(['attachable_type' => 'invoice', 'attachable_id' => 1, 'collection' => 'lampiran', 'disk' => 'local', 'path' => $referenced, 'original_name' => 'kept.pdf', 'mime' => 'application/pdf', 'size' => 1, 'created_at' => now(), 'updated_at' => now()]);

    expect(Artisan::call('admin:attachments-sweep'))->toBe(0);

    Storage::disk('local')->assertMissing($orphan);
    Storage::disk('local')->assertExists($fresh);
    Storage::disk('local')->assertExists($referenced);
    Storage::disk('local')->assertExists($export);
    $row = ImmutableActivity::query()->where('event', 'attachments_swept')->sole();
    expect($row->log_name)->toBe('system')
        ->and($row->properties?->get('meta'))->toBe(['files' => 1]);
});
