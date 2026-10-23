<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Yuan\AdminCore\Exports\QueuedExport;

/** A queued download is ready (spec §2.2). */
final class ExportCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly QueuedExport $export,
    ) {}
}
