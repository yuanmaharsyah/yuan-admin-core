<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Registry\ModuleRegistry;

final class ModulesClearCommand extends Command
{
    protected $signature = 'modules:clear';

    protected $description = 'Remove the module definition cache file';

    public function handle(ModuleRegistry $registry): int
    {
        $registry->clearCache();

        $this->components->info('Module definition cache cleared successfully.');

        return self::SUCCESS;
    }
}
