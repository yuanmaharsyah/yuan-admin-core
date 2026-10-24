<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yuan\AdminCore\Attachments\AttachmentSlot;
use Yuan\AdminCore\Attachments\AttachmentUploads;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

// Named attachment slots: each has its own rules, a required slot may stay
// empty once it holds a file, and a model without slots gets `lampiran`.

/**
 * @param  array<string, mixed>  $files
 */
function slotFails(AttachmentSlot $slot, array $files, bool $filled = false): bool
{
    return Validator::make(['files' => $files], ['files' => ['array'], ...$slot->rules('ktp', $filled)])->fails();
}

it('checks a single slot: one file, its types and size, required unless already filled', function (): void {
    $slot = AttachmentSlot::single('KTP')->mimes(['pdf'])->maxKb(100)->required();

    expect(slotFails($slot, ['ktp' => UploadedFile::fake()->create('ktp.pdf', 50, 'application/pdf')]))->toBeFalse()
        ->and(slotFails($slot, []))->toBeTrue()
        ->and(slotFails($slot, [], filled: true))->toBeFalse()
        ->and(slotFails($slot, ['ktp' => UploadedFile::fake()->create('ktp.png', 50, 'image/png')]))->toBeTrue()
        ->and(slotFails($slot, ['ktp' => UploadedFile::fake()->create('ktp.pdf', 101, 'application/pdf')]))->toBeTrue()
        ->and(slotFails($slot, ['ktp' => [UploadedFile::fake()->create('a.pdf', 1, 'application/pdf')]]))->toBeTrue();
});

it('checks every file of a multiple slot, and an optional slot may stay empty', function (): void {
    $slot = AttachmentSlot::multiple('Berkas')->mimes(['pdf']);

    expect(slotFails($slot, []))->toBeFalse()
        ->and(slotFails($slot, ['ktp' => [UploadedFile::fake()->create('a.pdf', 1, 'application/pdf'), UploadedFile::fake()->create('b.pdf', 1, 'application/pdf')]]))->toBeFalse()
        ->and(slotFails($slot, ['ktp' => [UploadedFile::fake()->create('a.pdf', 1, 'application/pdf'), UploadedFile::fake()->create('b.exe', 1)]]))->toBeTrue();
});

it('gives a model without attachmentSlots() one lampiran slot with the configured limits', function (): void {
    $slots = AttachmentUploads::slots(new DemoItem);

    expect(array_keys($slots))->toBe(['lampiran'])
        ->and($slots['lampiran']->multiple)->toBeTrue()
        ->and($slots['lampiran']->mimes)->toBe(config('admin-core.attachments.mimes'))
        ->and($slots['lampiran']->maxKb)->toBe(config('admin-core.attachments.max_kb'));
});

it('removes the files it stored when saving fails (here: no attachments table)', function (): void {
    Storage::fake('local');
    $unsaved = new DemoItem;

    expect(fn () => AttachmentUploads::save($unsaved, ['lampiran' => [UploadedFile::fake()->create('a.pdf', 1, 'application/pdf')]], null))->toThrow(QueryException::class);

    expect(Storage::disk('local')->allFiles())->toBe([]);
});
