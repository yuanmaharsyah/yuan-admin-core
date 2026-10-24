<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Data\Widgets\StatWidgetData;

/** A permission nobody synced (the module has no `export` action). */
final class Unsynced extends Widget
{
    public function key(): string
    {
        return 'leaky.total';
    }

    public function label(): string
    {
        return 'Total';
    }

    public function type(): WidgetType
    {
        return WidgetType::Stat;
    }

    public function permission(): string
    {
        return 'leaky.export';
    }

    public function data(Authenticatable $user, DashboardRange $range): StatWidgetData
    {
        return new StatWidgetData(value: 0);
    }
}
