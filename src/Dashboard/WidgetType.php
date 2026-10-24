<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Dashboard;

use Yuan\AdminCore\Data\Widgets\BarChartWidgetData;
use Yuan\AdminCore\Data\Widgets\SegmentWidgetData;
use Yuan\AdminCore\Data\Widgets\StatStackWidgetData;
use Yuan\AdminCore\Data\Widgets\StatWidgetData;
use Yuan\AdminCore\Data\Widgets\TableWidgetData;

/** The five widget types (spec §7.2) and the Data object `data()` returns for each. */
enum WidgetType: string
{
    case Stat = 'stat';
    case StatStack = 'stat_stack';
    case Segment = 'segment';
    case BarChart = 'bar_chart';
    case Table = 'table';

    /**
     * @return class-string
     */
    public function dataClass(): string
    {
        return match ($this) {
            self::Stat => StatWidgetData::class,
            self::StatStack => StatStackWidgetData::class,
            self::Segment => SegmentWidgetData::class,
            self::BarChart => BarChartWidgetData::class,
            self::Table => TableWidgetData::class,
        };
    }
}
