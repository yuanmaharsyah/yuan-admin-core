<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Yuan\AdminCore\Permissions\Level;

/**
 * A level's permissions were saved through the matrix (spec §6.1).
 */
final class LevelPermissionsUpdated
{
    use Dispatchable;

    /**
     * @param  list<string>  $added
     * @param  list<string>  $removed
     */
    public function __construct(
        public readonly Level $level,
        public readonly array $added,
        public readonly array $removed,
    ) {}
}
