<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Files on a record (spec §7) in named slots: private disk, signed 5-minute
 * download URLs authorized by the record's `view` policy; deleting needs
 * `update`. Files are uploaded with the record's form (AttachmentUploads).
 * Routes: `AttachmentController::routes(Model::class)`.
 */
trait HasAttachments
{
    /**
     * The record's files, newest first; only one slot's with a key.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(?string $collection = null): MorphMany
    {
        $files = $this->morphMany(Attachment::class, 'attachable')->latest('id');

        return $collection === null ? $files : $files->where('collection', $collection);
    }

    /**
     * Slot key => slot, in display order; override to name the slots. The
     * default is one `lampiran` slot with the `admin-core.attachments` limits.
     *
     * @return array<string, AttachmentSlot>
     */
    public function attachmentSlots(): array
    {
        return ['lampiran' => AttachmentSlot::multiple(__('Lampiran'))];
    }
}
