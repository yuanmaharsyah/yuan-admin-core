---
name: admin-dashboard-widget
description: "Use when adding a card, KPI, chart or table to Beranda (the dashboard) for a module."
---

# Add a Beranda widget

```bash
php artisan make:widget Kendaraan PajakJatuhTempo --type=stat --size=sm
```

Types: `stat`, `stat_stack`, `segment`, `bar_chart`, `table`, `area_chart`, `donut` (counts by an enum column, like `segment`), `list`. How each one looks: skill `admin-ui-components`. It writes `modules/{Name}/Widgets/{Widget}.php` with working code through `visibleTo()`, its test (permission, scope, time budget) and its line in `module.php` → `widgets`. Not for a superadmin_only module. Then adjust `data()` to what the widget should really show.

By hand, the same parts:

1. The class extends `Yuan\AdminCore\Dashboard\Widget`: `key()` (prefixed with the slug, e.g. `kendaraans.pajak_jatuh_tempo`), `label()`, `type()`, optionally `size()`, `order()`, `href()`, `emptyText()`. Example: `vendor/yuanmaharsyah/admin-core/modules/AuditLog/Widgets/FailedLogins.php`.
2. `data(Authenticatable $user, DashboardRange $range)`: start from `{Name}::query()->visibleTo($user)`, filter by `$range->from` / `$range->to` if `usesRange()`, return the type's Data object (`StatWidgetData`, `StatStackWidgetData`, `SegmentWidgetData`, `BarChartWidgetData`, `TableWidgetData`, `AreaChartWidgetData`, `DonutWidgetData`, `ListWidgetData`). A change against the previous range: `DashboardRange::change($current, $previous)` with the previous figure from `$range->previous()`. `BarChartWidgetData` also takes `negative` (a series below zero), `compare` (a series beside each bar, named by `series` / `compare_series`) and `summary` (side-panel totals). A `stat` with `highlight()` returning true shows as a large figure in Beranda's welcome card (two at most) instead of its own card. In the boilerplate repository, the demo Invoice module (`skeleton/modules/Invoice/Widgets`) has one widget of every new type; a project removes that module with `admin:remove-demo`.
3. List it in `module.php` → `widgets`. Set `permission()` if it isn't `{slug}.view`. Keep `sharedCache()` false unless every user of a level sees the same numbers.
4. Tests: `ModuleAssertions::assertWidgetHiddenWithout()`, `ModuleAssertions::assertWidgetUsesScope()`, `ModuleAssertions::assertWidgetWithinBudget()` (seed realistic rows first).
5. `composer check`.
