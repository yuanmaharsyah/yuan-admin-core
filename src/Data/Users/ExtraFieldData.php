<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Users;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\OptionData;

/**
 * One extra user field from the project's UserFormExtension. `type` is
 * `text`, `number` or `select` (with `options`).
 */
#[TypeScript]
final class ExtraFieldData extends Data
{
    /**
     * @param  'text'|'number'|'select'  $type
     * @param  list<OptionData>  $options
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public bool $required = false,
        public ?string $help = null,
        public array $options = [],
    ) {}
}
