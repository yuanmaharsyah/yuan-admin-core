<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A Beranda card without its data (frontend-behavior §8.6); the data arrives in the deferred prop `prop`. */
#[TypeScript]
final class WidgetMetaData extends Data
{
    public function __construct(
        public string $key,
        public string $prop,
        public string $label,
        #[LiteralTypeScriptType("'stat' | 'stat_stack' | 'segment' | 'bar_chart' | 'table'")]
        public string $type,
        #[LiteralTypeScriptType("'sm' | 'md' | 'lg' | 'full'")]
        public string $size,
        public bool $tall,
        public bool $uses_range,
        public ?string $href,
        // Shown when the data set is empty.
        public string $empty,
    ) {}
}
