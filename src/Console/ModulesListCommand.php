<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Registry\ModuleRegistry;

final class ModulesListCommand extends Command
{
    protected $signature = 'modules:list';

    protected $description = 'List every module with its root, slug, group and actions';

    public function handle(ModuleRegistry $registry): int
    {
        $rows = [];

        foreach ($registry->all() as $module) {
            $rows[] = [
                $module->name,
                $module->root,
                $module->slug,
                $module->group,
                implode(', ', $module->actions),
                $registry->isEnabled($module) ? 'yes' : 'no',
                $module->superadminOnly ? 'yes' : 'no',
            ];
        }

        $this->table(['Module', 'Root', 'Slug', 'Group', 'Actions', 'Enabled', 'Superadmin only'], $rows);

        return self::SUCCESS;
    }
}
