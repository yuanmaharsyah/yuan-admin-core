<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Widgets;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An `area_chart` widget (`AreaChartCard`): a headline figure with its change, over a soft area of `points` (a date `Y-m-d` key shows as a day). */
#[TypeScript]
final class AreaChartWidgetData extends Data
{
    /**
     * @param  list<WidgetFigureData>  $points
     */
    public function __construct(
        public int|float $value,
        public array $points,
        #[LiteralTypeScriptType("'number' | 'currency'")]
        public string $format = 'number',
        // Percent against the previous range (DashboardRange::change()).
        public ?float $change = null,
        public ?string $caption = null,
    ) {}
}
