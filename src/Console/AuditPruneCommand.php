<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Audit\AuditPruner;

final class AuditPruneCommand extends Command
{
    protected $signature = 'admin:audit-prune';

    protected $description = 'Delete audit rows older than admin-core.audit.retention_days (does nothing when it is null)';

    public function handle(AuditPruner $pruner): int
    {
        $deleted = $pruner->prune();

        if ($deleted === null) {
            $this->components->info('Audit retention is off (admin-core.audit.retention_days is null); nothing deleted.');
        } else {
            $this->components->info("Deleted {$deleted} audit rows.");
        }

        return self::SUCCESS;
    }
}
