<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Data\Widgets\StatWidgetData;

/** A key without the module's slug, and so a permission nobody synced. */
final class Misnamed extends Widget
{
    public function key(): string
    {
        return 'other.total';
    }

    public function label(): string
    {
        return 'Total';
    }

    public function type(): WidgetType
    {
        return WidgetType::Stat;
    }

    public function data(Authenticatable $user, DashboardRange $range): StatWidgetData
    {
        return new StatWidgetData(value: 0);
    }
}
