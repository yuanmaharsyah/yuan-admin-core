<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Audit;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One audit row in the Log Aktivitas list or a Riwayat tab. `causer_name` is
 * null for a system event or a failed sign-in, where `attempted_username`
 * is the name that was typed. `batch_href` opens the Log Aktivitas on the
 * rest of a multi-row operation. `diff` is filled for the Riwayat tab only.
 */
#[TypeScript]
final class ActivityItemData extends Data
{
    /**
     * @param  'neutral'|'success'|'warning'|'info'|'destructive'  $tone
     * @param  list<DiffRowData>  $diff
     */
    public function __construct(
        public int $id,
        public string $created_at,
        public ?string $causer_name,
        public ?string $causer_level,
        public ?string $attempted_username,
        public string $module_label,
        public string $event,
        public string $event_label,
        #[LiteralTypeScriptType("'neutral' | 'success' | 'warning' | 'info' | 'destructive'")]
        public string $tone,
        public ?string $subject_label,
        public ?string $subject_type,
        public ?string $summary,
        public ?string $batch_uuid,
        public ?int $batch_size,
        public ?string $batch_href,
        public array $diff = [],
    ) {}
}
