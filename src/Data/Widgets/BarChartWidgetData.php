<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A `bar_chart` widget (`BarChartCard`): one bar per `key` (a date `Y-m-d` shows as a day), `label` names the value in the tooltip. */
#[TypeScript]
final class BarChartWidgetData extends Data
{
    /**
     * @param  list<WidgetFigureData>  $bars
     */
    public function __construct(
        public array $bars,
        public string $unit,
    ) {}
}
