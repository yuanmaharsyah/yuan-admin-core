<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class BreadcrumbData extends Data
{
    public function __construct(
        public string $label,
        // Null for the current page (the last crumb).
        public ?string $href = null,
    ) {}
}
