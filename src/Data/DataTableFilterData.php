<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One toolbar filter chip: `filter[{key}]` with a fixed list of options.
 * `column` is what it matches when that isn't the key itself (`is_active`,
 * or a relation's column like `roles.id`); it never reaches the page.
 */
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
        #[Hidden]
        public ?string $column = null,
    ) {}
}
