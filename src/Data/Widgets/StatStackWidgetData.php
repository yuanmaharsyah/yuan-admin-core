<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A `stat_stack` widget: a column of figures (`StatStack`). */
#[TypeScript]
final class StatStackWidgetData extends Data
{
    /**
     * @param  list<WidgetFigureData>  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
