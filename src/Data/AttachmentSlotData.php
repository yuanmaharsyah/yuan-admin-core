<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One attachment slot of a record: its rules (for the form's FileField) and its files. */
#[TypeScript]
final class AttachmentSlotData extends Data
{
    /**
     * @param  list<string>  $accept  Allowed extensions, without the dot.
     * @param  list<AttachmentData>  $items
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $multiple,
        public bool $required,
        public array $accept,
        public int $max_kb,
        public array $items,
    ) {}
}
