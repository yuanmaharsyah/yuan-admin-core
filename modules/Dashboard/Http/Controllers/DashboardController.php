<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Dashboard\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\Data;
use Yuan\AdminCore\Dashboard\DashboardRange;
use Yuan\AdminCore\Dashboard\Widget;
use Yuan\AdminCore\Dashboard\WidgetRegistry;
use Yuan\AdminCore\Data\Widgets\WidgetMetaData;
use Yuan\AdminCore\Modules\Dashboard\Data\DashboardPageData;

/**
 * Beranda (spec §7.2): the widget list first, then each widget's data as its
 * own deferred group, rescued, so one failing widget leaves the others.
 */
final class DashboardController
{
    public function index(Request $request, WidgetRegistry $registry): Response
    {
        Gate::authorize('dashboard.view');
        /** @var Authenticatable $user */
        $user = $request->user();
        $range = DashboardRange::from($request->query('range'));
        $widgets = $registry->forUser($user);

        $props = ['dashboard' => new DashboardPageData(
            widgets: array_map(fn (Widget $widget): WidgetMetaData => $widget->meta(), $widgets),
            range: $range->key,
        )];

        foreach ($widgets as $widget) {
            $props[$widget->prop()] = Inertia::defer(fn (): Data => $widget->resolve($user, $range), $widget->prop(), rescue: true);
        }

        return Inertia::render('Dashboard::Index', $props);
    }
}
