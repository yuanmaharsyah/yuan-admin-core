---
name: admin-dashboard-widget
description: "Use when adding a card, KPI, chart or table to Beranda (the dashboard) for a module."
---

# Add a Beranda widget

```bash
php artisan make:widget Kendaraan PajakJatuhTempo --type=stat --size=sm
```

Types: `stat`, `stat_stack`, `segment`, `bar_chart`, `table`. It writes `modules/{Name}/Widgets/{Widget}.php` with working code through `visibleTo()`, its test (permission, scope, time budget) and its line in `module.php` → `widgets`. Not for a superadmin_only module. Then adjust `data()` to what the widget should really show.

By hand, the same parts:

1. The class extends `Yuan\AdminCore\Dashboard\Widget`: `key()` (prefixed with the slug, e.g. `kendaraans.pajak_jatuh_tempo`), `label()`, `type()`, optionally `size()`, `order()`, `href()`, `emptyText()`. Example: `vendor/yuan/admin-core/modules/AuditLog/Widgets/FailedLogins.php`.
2. `data(Authenticatable $user, DashboardRange $range)`: start from `{Name}::query()->visibleTo($user)`, filter by `$range->from` / `$range->to` if `usesRange()`, return the type's Data object (`StatWidgetData`, `StatStackWidgetData`, `SegmentWidgetData`, `BarChartWidgetData`, `TableWidgetData`).
3. List it in `module.php` → `widgets`. Set `permission()` if it isn't `{slug}.view`. Keep `sharedCache()` false unless every user of a level sees the same numbers.
4. Tests: `ModuleAssertions::assertWidgetHiddenWithout()`, `ModuleAssertions::assertWidgetUsesScope()`, `ModuleAssertions::assertWidgetWithinBudget()` (seed realistic rows first).
5. `composer check`.
