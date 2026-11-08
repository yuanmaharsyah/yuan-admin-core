<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One row of a `list` widget: icon, title, subtitle and a right-hand value;
 * `tone` colours the value, `change` adds a percent pill, `progress` (0 to 1)
 * a thin bar under the title.
 */
#[TypeScript]
final class ListRowData extends Data
{
    public function __construct(
        public string $key,
        public string $title,
        public string $value,
        public string $icon = 'circle',
        public ?string $subtitle = null,
        #[LiteralTypeScriptType("'neutral' | 'success' | 'destructive'")]
        public string $tone = 'neutral',
        public ?string $href = null,
        public ?float $change = null,
        public ?float $progress = null,
    ) {}
}
