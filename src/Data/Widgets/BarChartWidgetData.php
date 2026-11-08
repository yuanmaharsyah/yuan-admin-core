<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A `bar_chart` widget (`BarChartCard`): one bar per `key` (a date `Y-m-d` shows
 * as a day), `unit` names the value in the tooltip. `negative` (same keys) draws
 * a second series below zero; `compare` (same keys) draws a second bar beside
 * each one, named by `series` / `compare_series` in the legend; `summary` (at
 * most three) fills a side panel.
 */
#[TypeScript]
final class BarChartWidgetData extends Data
{
    public const int MAX_SUMMARY = 3;

    /**
     * @param  list<WidgetFigureData>  $bars
     * @param  list<WidgetFigureData>  $negative
     * @param  list<WidgetFigureData>  $summary
     * @param  list<WidgetFigureData>  $compare
     */
    public function __construct(
        public array $bars,
        public string $unit,
        public array $negative = [],
        public array $summary = [],
        public array $compare = [],
        public ?string $series = null,
        public ?string $compare_series = null,
    ) {
        $this->summary = array_slice($summary, 0, self::MAX_SUMMARY);
    }
}
