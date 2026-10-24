<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Data\Widgets\StatWidgetData;

/** A widget of the `invoices` module in the ModuleDefinition tests. */
final class InvoiceTotal extends Widget
{
    public function key(): string
    {
        return 'invoices.total';
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
