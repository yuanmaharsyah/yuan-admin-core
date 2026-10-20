<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Yuan\AdminCore\Audit\NotAudited;

/**
 * A file on a private disk, attached to a record (HasAttachments). Its
 * stored name is random; `original_name` is metadata only.
 *
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property int|null $uploaded_by
 * @property Carbon $created_at
 * @property-read Model|null $uploader
 */
#[NotAudited(reason: 'Uploads and deletions are audited on the parent record as attachment_uploaded / attachment_deleted.')]
final class Attachment extends Model
{
    protected $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'uploaded_by'];

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function uploader(): BelongsTo
    {
        /** @var class-string<Model> $user */
        $user = config()->string('auth.providers.users.model');

        return $this->belongsTo($user, 'uploaded_by');
    }

    protected function casts(): array
    {
        return ['attachable_id' => 'integer', 'size' => 'integer', 'uploaded_by' => 'integer'];
    }
}
