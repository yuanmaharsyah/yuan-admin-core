<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PaginationLinksData extends Data
{
    public function __construct(
        public string $first,
        public string $last,
        public ?string $prev,
        public ?string $next,
    ) {}
}
