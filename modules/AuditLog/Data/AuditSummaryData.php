<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The Log Aktivitas summary for one range, user and module (reference
 * `audit`): KPI tiles, the stat stack with the login bars, "Aktivitas per
 * Jenis" and "Aktivitas per Hari" (one bar per week above 60 days).
 */
#[TypeScript]
final class AuditSummaryData extends Data
{
    /**
     * @param  list<AuditCountData>  $by_type
     * @param  list<AuditCountData>  $by_day
     * @param  'day'|'week'  $bucket
     */
    public function __construct(
        public int $total,
        public int $active_users,
        public int $failed_logins,
        public int $downloads,
        public int $rows_downloaded,
        public int $permission_changes,
        public int $logins,
        public array $by_type,
        public array $by_day,
        #[LiteralTypeScriptType("'day' | 'week'")]
        public string $bucket,
        public string $from,
        public string $to,
    ) {}
}
