<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A `donut` widget (`DonutCard`): a headline figure with its change, and a ring of at most five `segments` with a legend. */
#[TypeScript]
final class DonutWidgetData extends Data
{
    public const int MAX_SEGMENTS = 5;

    /**
     * @param  list<WidgetFigureData>  $segments
     */
    public function __construct(
        public int|float $value,
        public array $segments,
        #[LiteralTypeScriptType("'number' | 'currency'")]
        public string $format = 'number',
        public ?float $change = null,
        public ?string $caption = null,
    ) {
        $this->segments = array_slice($segments, 0, self::MAX_SEGMENTS);
    }
}
