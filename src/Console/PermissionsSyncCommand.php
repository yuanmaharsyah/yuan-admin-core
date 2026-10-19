<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Permissions\PermissionSynchronizer;

final class PermissionsSyncCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Make the permissions table match the actions declared by the modules';

    public function handle(PermissionSynchronizer $synchronizer): int
    {
        ['created' => $created, 'removed' => $removed] = $synchronizer->sync();

        $this->components->info("Permissions synced: {$created} created, {$removed} removed.");

        return self::SUCCESS;
    }
}
