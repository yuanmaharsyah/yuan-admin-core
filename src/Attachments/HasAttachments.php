<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Files on a record (spec §7): private disk, signed 5-minute download URLs
 * authorized by the record's `view` policy; uploading and deleting need
 * `update`. Routes: `AttachmentController::routes(Model::class)`.
 */
trait HasAttachments
{
    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest('id');
    }

    /**
     * Allowed extensions and the size limit; override to narrow the
     * `admin-core.attachments` defaults.
     *
     * @return array{mimes: list<string>, max_kb: int}
     */
    public function attachmentRules(): array
    {
        /** @var list<string> $mimes */
        $mimes = config()->array('admin-core.attachments.mimes');

        return ['mimes' => $mimes, 'max_kb' => config()->integer('admin-core.attachments.max_kb')];
    }
}
