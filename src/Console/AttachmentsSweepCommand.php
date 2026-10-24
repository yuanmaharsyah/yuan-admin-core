<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Attachments\AttachmentSweeper;

final class AttachmentsSweepCommand extends Command
{
    protected $signature = 'admin:attachments-sweep';

    protected $description = 'Delete attachment files without an attachments row, older than 24 hours';

    public function handle(AttachmentSweeper $sweeper): int
    {
        $this->components->info("Deleted {$sweeper->sweep()} orphaned attachment files.");

        return self::SUCCESS;
    }
}
