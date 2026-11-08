<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Components\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One row of the showcase DataTable (documentation fixture, never app data). */
#[TypeScript]
final class SampleRowData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $city,
        #[LiteralTypeScriptType("'paid' | 'sent' | 'draft'")]
        public string $status,
        public int $total,
        public string $date,
    ) {}
}
