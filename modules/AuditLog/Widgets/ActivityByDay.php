<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetSize;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Data\Widgets\BarChartWidgetData;
use Yuan\AdminCore\Data\Widgets\WidgetFigureData;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditCountData;
use Yuan\AdminCore\Modules\AuditLog\Data\AuditFilterData;
use Yuan\AdminCore\Modules\AuditLog\Services\AuditSummary;

/** "Aktivitas per Hari" on Beranda: the Log Aktivitas summary's bars (week bars above 60 days), each linking to that day. */
final class ActivityByDay extends Widget
{
    public function __construct(private readonly AuditSummary $summary) {}

    public function key(): string
    {
        return 'audit-log.activity_by_day';
    }

    public function label(): string
    {
        return 'Aktivitas per Hari';
    }

    public function type(): WidgetType
    {
        return WidgetType::BarChart;
    }

    public function size(): WidgetSize
    {
        return WidgetSize::Full;
    }

    public function order(): int
    {
        return 50;
    }

    // The summary has its own 5-minute cache, per viewer kind.
    public function cacheSeconds(): int
    {
        return 0;
    }

    public function data(Authenticatable $user, DashboardRange $range): BarChartWidgetData
    {
        $summary = $this->summary->for(AuditFilterData::from(['range' => $range->key]), $user);

        return new BarChartWidgetData(
            bars: array_map(fn (AuditCountData $bar): WidgetFigureData => new WidgetFigureData(
                key: $bar->key,
                label: $bar->label,
                value: $bar->value,
                href: $summary->bucket === 'day' ? route('audit-log.index', ['range' => 'custom', 'from' => $bar->key, 'to' => $bar->key]) : null,
            ), $summary->by_day),
            unit: __('aktivitas'),
        );
    }
}
