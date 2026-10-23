<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Users;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An extra field's stored value (for the form) and what the detail page shows (a select's label). */
#[TypeScript]
final class ExtraValueData extends Data
{
    public function __construct(
        public string $name,
        public string $label,
        public string|int|float|null $value,
        public ?string $display,
    ) {}
}
