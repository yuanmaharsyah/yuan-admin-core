<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Audit;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One changed field of an audit row: before and after, as stored (masked fields already masked). */
#[TypeScript]
final class DiffRowData extends Data
{
    public function __construct(
        public string $field,
        public ?string $old,
        public ?string $new,
        public bool $masked,
    ) {}
}
