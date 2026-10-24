<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Exports;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Maatwebsite\Excel\Facades\Excel;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Events\ExportCompleted;

/**
 * Writes a large download to `admin-core.exports.disk` for the user who asked
 * for it, who is also the causer of its `exported` audit row. After the last
 * try the row is marked failed with a plain message for Unduhan (the
 * exception itself stays in `failed_jobs`, never shown to the user).
 */
#[Tries(3)]
#[Backoff([10, 60])]
#[Timeout(600)]
final class ExportJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  class-string<ListExport<*>>  $export
     */
    public function __construct(
        public QueuedExport $queued,
        public Model&Authenticatable $user,
        public string $export,
    ) {}

    public function handle(): void
    {
        $filters = $this->queued->filters;
        $format = is_string($filters['format'] ?? null) && isset(ExportManager::FORMATS[$filters['format']]) ? $filters['format'] : 'xlsx';
        unset($filters['format']);
        $export = new $this->export($this->user, $filters);
        $path = 'exports/'.$this->queued->id.'/'.ExportManager::fileName($this->queued->module, $format);
        $rows = $export->query()->count();

        Excel::store($export, $path, config()->string('admin-core.exports.disk'), ExportManager::FORMATS[$format]);

        $this->queued->update([
            'status' => ExportStatus::Ready,
            'path' => $path,
            'row_count' => $rows,
            'expires_at' => now()->addHours(config()->integer('admin-core.exports.ttl_hours')),
        ]);
        Audit::actingAs($this->user, fn () => Audit::record(
            AuditEvent::Exported,
            meta: ['rows' => $rows, 'filters' => $filters, 'format' => $format, 'queued' => true],
            module: $this->queued->module,
        ));
        ExportCompleted::dispatch($this->queued);
    }

    public function failed(): void
    {
        // The user sees a plain message; the exception stays in failed_jobs and the logs.
        $this->queued->update(['status' => ExportStatus::Failed, 'error' => __('Ekspor gagal. Coba lagi, atau hubungi administrator.')]);
    }
}
