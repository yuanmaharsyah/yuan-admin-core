<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Audit retention (spec §7, `audit.retention_days`): the only code that
 * deletes activity rows. It removes rows older than the retention in chunks
 * through the query builder (the model refuses deletes) and leaves one
 * `audit_pruned` row behind. With no retention set it does nothing.
 */
final class AuditPruner
{
    private const int CHUNK = 5000;

    /** The number of rows deleted, or null when retention is off. */
    public function prune(): ?int
    {
        $days = config('admin-core.audit.retention_days');

        if (! is_numeric($days) || (int) $days < 1) {
            return null;
        }

        $before = CarbonImmutable::today()->subDays((int) $days);
        $deleted = 0;

        do {
            $ids = DB::table('activity_log')->where('created_at', '<', $before)->orderBy('id')->limit(self::CHUNK)->pluck('id');
            $deleted += $ids->isEmpty() ? 0 : DB::table('activity_log')->whereIn('id', $ids)->delete();
        } while ($ids->count() === self::CHUNK);

        Audit::record(AuditEvent::AuditPruned, meta: ['rows' => $deleted, 'before' => $before->toDateString(), 'retention_days' => (int) $days]);

        return $deleted;
    }
}
