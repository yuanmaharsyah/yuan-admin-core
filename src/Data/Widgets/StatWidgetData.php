<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A `stat` widget: one KPI (`StatTile`); `icon` is a lucide name, `badge` a warning next to the value. */
#[TypeScript]
final class StatWidgetData extends Data
{
    public function __construct(
        public int|float $value,
        public string $icon = 'activity',
        #[LiteralTypeScriptType("'number' | 'currency'")]
        public string $format = 'number',
        public ?string $badge = null,
    ) {}
}
