<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A value and its label, for filters and selects. */
#[TypeScript]
final class OptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
