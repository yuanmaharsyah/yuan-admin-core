<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One toolbar filter chip: `filter[{key}]` with a fixed list of options. */
#[TypeScript]
final class DataTableFilterData extends Data
{
    /**
     * @param  list<OptionData>  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $options,
    ) {}
}
