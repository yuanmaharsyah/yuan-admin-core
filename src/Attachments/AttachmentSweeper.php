<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

use Illuminate\Support\Facades\Storage;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;

/**
 * Removes attachment files no `attachments` row points at (left by a
 * rolled-back save or a crash between storing and saving), once they are
 * older than a day, so a save still in progress keeps its file. Logs one
 * `attachments_swept` row per run.
 */
final class AttachmentSweeper
{
    public const string FOLDER = 'attachments';

    public const int MIN_AGE_HOURS = 24;

    /** The number of files deleted. */
    public function sweep(): int
    {
        $disk = config()->string('admin-core.attachments.disk');
        $storage = Storage::disk($disk);
        $cutoff = now()->subHours(self::MIN_AGE_HOURS)->getTimestamp();
        // ponytail: every path of the disk in memory; chunk by folder (attachments/{slug}/Y/m) past a few hundred thousand files.
        $known = array_flip(Attachment::query()->where('disk', $disk)->pluck('path')->all());
        $deleted = 0;

        foreach ($storage->allFiles(self::FOLDER) as $path) {
            if (! isset($known[$path]) && $storage->lastModified($path) < $cutoff) {
                $storage->delete($path);
                $deleted++;
            }
        }

        Audit::record(AuditEvent::AttachmentsSwept, meta: ['files' => $deleted]);

        return $deleted;
    }
}
