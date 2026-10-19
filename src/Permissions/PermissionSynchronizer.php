<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Permissions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Makes the `permissions` table equal `{slug}.{action}` of every enabled,
 * non-superadmin_only module (spec §6.2). The only place permissions are made.
 */
final readonly class PermissionSynchronizer
{
    public function __construct(private ModuleRegistry $registry, private PermissionRegistrar $permissions) {}

    /**
     * @return array{created: int, removed: int}
     */
    public function sync(): array
    {
        $guard = config()->string('auth.defaults.guard');
        $wanted = $this->wanted();

        $counts = DB::transaction(function () use ($guard, $wanted): array {
            $existing = Permission::query()->where('guard_name', $guard)->pluck('name')->all();
            $missing = array_diff_key($wanted, array_flip($existing));

            foreach ($missing as $name => [$module, $action]) {
                Permission::query()->create(['name' => $name, 'guard_name' => $guard, 'module' => $module, 'action' => $action]);
            }

            $removed = Permission::query()->where('guard_name', $guard)->whereNotIn('name', array_keys($wanted))->delete();

            return ['created' => count($missing), 'removed' => (int) $removed];
        });

        $this->permissions->forgetCachedPermissions();

        return $counts;
    }

    /**
     * @return array<string, array{string, string}> name => [module, action]
     */
    private function wanted(): array
    {
        $wanted = [];

        foreach ($this->registry->enabled() as $module) {
            if ($module->superadminOnly) {
                continue;
            }

            foreach ($module->actions as $action) {
                $wanted["{$module->slug}.{$action}"] = [$module->slug, $action];
            }
        }

        return $wanted;
    }
}
