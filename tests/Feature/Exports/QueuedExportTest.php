<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Yuan\AdminCore\Data\DownloadsData;
use Yuan\AdminCore\Exports\ExportJob;
use Yuan\AdminCore\Exports\ExportStatus;
use Yuan\AdminCore\Exports\ModuleExport;
use Yuan\AdminCore\Exports\QueuedExport;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

uses(RefreshDatabase::class);

function queuedExport(User $user, ExportStatus $status, ?string $expiresAt, ?string $path = null): QueuedExport
{
    return QueuedExport::query()->create([
        'user_id' => $user->id,
        'module' => 'demo',
        'filters' => [],
        'row_count' => 10,
        'status' => $status,
        'path' => $path,
        'expires_at' => $expiresAt,
    ]);
}

it('counts only the user\'s ready, unexpired downloads', function (): void {
    [$budi, $andi] = [makeUser('Budi'), makeUser('Andi')];
    queuedExport($budi, ExportStatus::Ready, now()->addHour()->toDateTimeString());
    queuedExport($budi, ExportStatus::Ready, now()->subMinute()->toDateTimeString());
    queuedExport($budi, ExportStatus::Processing, null);
    queuedExport($budi, ExportStatus::Failed, null);
    queuedExport($andi, ExportStatus::Ready, now()->addHour()->toDateTimeString());

    expect(DownloadsData::forUser($budi)->ready_count)->toBe(1)
        ->and(DownloadsData::forUser(null)->ready_count)->toBe(0);
});

it('prunes expired downloads with their files', function (): void {
    Storage::fake('local');
    $user = makeUser();
    Storage::disk('local')->put('exports/1/old.xlsx', 'x');
    Storage::disk('local')->put('exports/2/new.xlsx', 'x');
    queuedExport($user, ExportStatus::Ready, now()->subMinute()->toDateTimeString(), 'exports/1/old.xlsx');
    $new = queuedExport($user, ExportStatus::Ready, now()->addHour()->toDateTimeString(), 'exports/2/new.xlsx');

    command('model:prune', ['--model' => [QueuedExport::class]])->assertSuccessful();

    expect(QueuedExport::query()->pluck('id')->all())->toBe([$new->id]);
    Storage::disk('local')->assertMissing('exports/1/old.xlsx');
    Storage::disk('local')->assertExists('exports/2/new.xlsx');
});

it('marks a failed job with a plain message, never the exception', function (): void {
    $user = makeUser();
    $queued = queuedExport($user, ExportStatus::Processing, null);

    (new ExportJob($queued, $user, ModuleExport::class))->failed();

    expect($queued->fresh()?->only(['status', 'error']))->toBe([
        'status' => ExportStatus::Failed,
        'error' => 'Ekspor gagal. Coba lagi, atau hubungi administrator.',
    ]);
});
