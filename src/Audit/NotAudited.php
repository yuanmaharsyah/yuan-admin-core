<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Attribute;

/**
 * The only opt-out from `LogsModuleActivity` for a module model with business
 * columns (spec §9.1 AuditCoverageTest). The reason must say why.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NotAudited
{
    public function __construct(public string $reason) {}
}
