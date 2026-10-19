<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Registry\ModuleRegistry;

final class ModulesCacheCommand extends Command
{
    protected $signature = 'modules:cache';

    protected $description = 'Validate every module.php and cache the module definitions';

    public function handle(ModuleRegistry $registry): int
    {
        $registry->cache();

        $this->components->info('Module definitions cached successfully.');

        return self::SUCCESS;
    }
}
