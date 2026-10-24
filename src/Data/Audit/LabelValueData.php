<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Audit;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A label and its value in the activity drawer (context and notes); `mono` for ids. */
#[TypeScript]
final class LabelValueData extends Data
{
    public function __construct(
        public string $label,
        public string $value,
        public bool $mono = false,
    ) {}
}
