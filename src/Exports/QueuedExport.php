<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Yuan\AdminCore\Audit\NotAudited;

/**
 * A download too large to stream, written by ExportJob (table `exports`).
 * Rows and files are removed by `model:prune` once `expires_at` has passed.
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property array<string, mixed> $filters
 * @property int $row_count
 * @property ExportStatus $status
 * @property string|null $path
 * @property string|null $error
 * @property Carbon|null $expires_at
 */
#[NotAudited(reason: 'System bookkeeping of a download; the download itself is audited as `exported`.')]
final class QueuedExport extends Model
{
    use Prunable;

    protected $table = 'exports';

    protected $fillable = ['user_id', 'module', 'filters', 'row_count', 'status', 'path', 'error', 'expires_at'];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<', now());
    }

    protected function pruning(): void
    {
        if ($this->path !== null) {
            Storage::disk(config()->string('admin-core.exports.disk'))->delete($this->path);
        }
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'filters' => 'array',
            'row_count' => 'integer',
            'status' => ExportStatus::class,
            'expires_at' => 'datetime',
        ];
    }
}
