<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * The FilterBar's choices: the users the viewer may see (Super Admin accounts
 * only for a Super Admin), and every module plus Autentikasi and Sistem.
 * Super Admin-only modules are left out for other viewers: only Super Admin
 * acts there, so they would always be empty.
 */
final readonly class AuditFilterOptions
{
    public function __construct(private ModuleRegistry $registry) {}

    /**
     * @return list<OptionData>
     */
    public function users(Authenticatable $viewer): array
    {
        /** @var class-string<User> $model */
        $model = config()->string('admin-core.models.user');

        return array_values($model::query()->visibleTo($viewer)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Model $user): OptionData => new OptionData((string) $user->getKey(), (string) $user->getAttribute('name')))
            ->all());
    }

    /**
     * @return list<OptionData>
     */
    public function modules(Authenticatable $viewer): array
    {
        $superAdmin = method_exists($viewer, 'isSuperAdmin') && $viewer->isSuperAdmin();
        $modules = array_filter($this->registry->enabled(), fn (ModuleDefinition $module): bool => $superAdmin || ! $module->superadminOnly);
        usort($modules, fn (ModuleDefinition $a, ModuleDefinition $b): int => strcmp($a->label, $b->label));

        return [
            ...array_map(fn (ModuleDefinition $module): OptionData => new OptionData($module->slug, $module->label), $modules),
            new OptionData('auth', __('Autentikasi')),
            new OptionData('system', __('Sistem')),
        ];
    }
}
