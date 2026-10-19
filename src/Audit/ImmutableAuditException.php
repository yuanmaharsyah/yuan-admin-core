<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use LogicException;

final class ImmutableAuditException extends LogicException
{
    public function __construct()
    {
        parent::__construct('Audit rows are append-only: they cannot be updated or deleted.');
    }
}
