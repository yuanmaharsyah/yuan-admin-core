<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Audit;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The activity drawer (`?event_id=`): the row, its request context (user,
 * IP, device, source, request id), the changed fields, the other stored
 * details, and links to the record and to the rest of its batch.
 */
#[TypeScript]
final class ActivityDetailData extends Data
{
    /**
     * @param  list<LabelValueData>  $context
     * @param  list<DiffRowData>  $diff
     * @param  list<LabelValueData>  $notes
     */
    public function __construct(
        public ActivityItemData $item,
        public array $context,
        public array $diff,
        public array $notes,
        public ?string $subject_href,
        public ?string $batch_href,
    ) {}
}
