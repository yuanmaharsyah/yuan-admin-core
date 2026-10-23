<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Permissions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Data\LevelData;
use Yuan\AdminCore\Data\PermissionMatrixData;
use Yuan\AdminCore\Data\PermissionMatrixRowData;
use Yuan\AdminCore\Events\LevelPermissionsUpdated;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Reads and saves a level's permissions (spec §6.1). Rows are the enabled,
 * non-superadmin_only modules; the protected level is never editable.
 */
final readonly class PermissionMatrix
{
    public function __construct(private ModuleRegistry $registry) {}

    public function build(Level $level): PermissionMatrixData
    {
        $granted = $this->grantedBy($level);
        $columns = ModuleDefinition::STANDARD_ACTIONS;
        $rows = [];

        foreach ($this->modules() as $module) {
            $columns = [...$columns, ...array_diff($module->customActions(), $columns)];
            $rows[] = new PermissionMatrixRowData(
                slug: $module->slug,
                label: __($module->label),
                group: __($this->groups()[$module->group]['label'] ?? $module->group),
                actions: $module->actions,
                granted: $granted[$module->slug] ?? [],
            );
        }

        return new PermissionMatrixData(level: LevelData::fromLevel($level), columns: $columns, rows: $rows);
    }

    /**
     * Applies the dependency rule (any action implies view; removing view
     * clears the row), then saves in one transaction with one audit row.
     *
     * @param  array<string, list<string>>  $grants  Module slug => actions; a module left out gets none.
     *
     * @throws GuardRailViolation For the protected level.
     * @throws InvalidArgumentException For an unknown module or an undeclared action.
     */
    public function save(Level $level, array $grants): void
    {
        if ($level->is_protected) {
            throw GuardRailViolation::protectedLevel();
        }

        $modules = [];

        foreach ($this->modules() as $module) {
            $modules[$module->slug] = $module;
        }

        $before = $this->grantedBy($level);
        $names = [];

        foreach ($grants as $slug => $actions) {
            $module = $modules[$slug] ?? throw new InvalidArgumentException("Unknown matrix module [{$slug}].");

            if (($undeclared = array_diff($actions, $module->actions)) !== []) {
                throw new InvalidArgumentException("Module [{$slug}] does not declare [".implode(', ', $undeclared).'].');
            }

            $hadView = in_array('view', $before[$slug] ?? [], true);
            $actions = $hadView && ! in_array('view', $actions, true) ? [] : $actions;
            $actions = $actions === [] ? [] : array_unique(['view', ...$actions]);

            foreach ($actions as $action) {
                $names[] = "{$slug}.{$action}";
            }
        }

        $old = $level->permissions()->pluck('name')->all();
        $added = array_values(array_diff($names, $old));
        $removed = array_values(array_diff($old, $names));

        if ($added === [] && $removed === []) {
            return;
        }

        DB::transaction(fn () => Audit::batch(function () use ($level, $names, $added, $removed): void {
            $level->syncPermissions($names);
            Audit::record(AuditEvent::PermissionsUpdated, $level, ['added' => $added, 'removed' => $removed], 'levels');
        }));

        LevelPermissionsUpdated::dispatch($level, $added, $removed);
    }

    /**
     * @return list<ModuleDefinition>
     */
    private function modules(): array
    {
        $modules = array_values(array_filter($this->registry->enabled(), fn (ModuleDefinition $module): bool => ! $module->superadminOnly));
        $groups = $this->groups();
        usort($modules, fn (ModuleDefinition $a, ModuleDefinition $b): int => [$groups[$a->group]['order'] ?? 0, $a->order, $a->label] <=> [$groups[$b->group]['order'] ?? 0, $b->order, $b->label]);

        return $modules;
    }

    /**
     * The menu groups (`admin-core.menu.groups`), so the matrix groups rows like the menu.
     *
     * @return array<string, array{label: string, order: int}>
     */
    private function groups(): array
    {
        /** @var array<string, array{label: string, order: int}> */
        return config()->array('admin-core.menu.groups');
    }

    /**
     * @return array<string, list<string>> module slug => granted actions
     */
    private function grantedBy(Level $level): array
    {
        $granted = [];

        foreach ($level->permissions()->get(['module', 'action']) as $permission) {
            $granted[(string) $permission->getAttribute('module')][] = (string) $permission->getAttribute('action');
        }

        return $granted;
    }
}
