<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One labelled figure: a stat-stack row, a segment or a bar; `href` opens the module list filtered to it. */
#[TypeScript]
final class WidgetFigureData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public int|float $value,
        #[LiteralTypeScriptType("'number' | 'currency'")]
        public string $format = 'number',
        public ?string $href = null,
    ) {}
}
