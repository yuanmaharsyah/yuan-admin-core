<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Dashboard;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Yuan\AdminCore\Registry\ModuleRegistry;

/** The widgets of the enabled modules; per user, only the permitted ones, ordered, at most `admin-core.dashboard.max_widgets`. */
final readonly class WidgetRegistry
{
    public function __construct(private ModuleRegistry $modules) {}

    /**
     * @return list<Widget>
     */
    public function all(): array
    {
        $widgets = [];

        foreach ($this->modules->enabled() as $module) {
            foreach ($module->widgets as $class) {
                $widget = app($class);

                if ($widget instanceof Widget) {
                    $widgets[] = $widget;
                }
            }
        }

        return $widgets;
    }

    /**
     * @return list<Widget>
     */
    public function forUser(Authenticatable $user): array
    {
        $widgets = array_values(array_filter($this->all(), fn (Widget $widget): bool => Gate::forUser($user)->allows($widget->permission())));
        usort($widgets, fn (Widget $a, Widget $b): int => [$a->order(), $a->key()] <=> [$b->order(), $b->key()]);

        return array_slice($widgets, 0, config()->integer('admin-core.dashboard.max_widgets'));
    }
}
