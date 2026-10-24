<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A record's attachment slots, in display order (`AttachmentController::data()`). */
#[TypeScript]
final class AttachmentsData extends Data
{
    /**
     * @param  list<AttachmentSlotData>  $slots
     */
    public function __construct(
        public array $slots,
    ) {}

}
