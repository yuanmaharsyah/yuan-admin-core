<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A `segment` widget: parts of a whole (`SegmentBar`), at most five. */
#[TypeScript]
final class SegmentWidgetData extends Data
{
    /**
     * @param  list<WidgetFigureData>  $segments
     */
    public function __construct(
        public array $segments,
    ) {}
}
