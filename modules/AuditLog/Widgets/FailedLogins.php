<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Data\Widgets\StatWidgetData;

/** "Login Gagal" on Beranda: failed sign-ins in the range the viewer may see. */
final class FailedLogins extends Widget
{
    public function key(): string
    {
        return 'audit-log.failed_logins';
    }

    public function label(): string
    {
        return 'Login Gagal';
    }

    public function type(): WidgetType
    {
        return WidgetType::Stat;
    }

    public function order(): int
    {
        return 10;
    }

    public function href(): string
    {
        return route('audit-log.index', ['filter' => ['event' => 'auth']]);
    }

    public function data(Authenticatable $user, DashboardRange $range): StatWidgetData
    {
        $count = ImmutableActivity::query()->visibleTo($user)
            ->where('event', AuditEvent::LoginFailed->value)
            ->whereBetween('created_on', [$range->from->toDateString(), $range->to->toDateString()])
            ->count();

        if ($count === 0) {
            return new StatWidgetData(value: 0, icon: 'shield-alert');
        }

        return new StatWidgetData(value: $count, icon: 'shield-alert', badge: __('Perlu dicek'));
    }
}
