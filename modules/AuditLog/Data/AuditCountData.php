<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\AuditLog\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One segment or bar of the summary: an event group (`key` = AuditCategory) or a day / week start (`key` = Y-m-d). */
#[TypeScript]
final class AuditCountData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public int $value,
    ) {}
}
