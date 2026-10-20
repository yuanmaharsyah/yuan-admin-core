<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The JSON answer of a module's `lookup` route: up to 20 matches for a RelationSelect. */
#[TypeScript]
final class LookupData extends Data
{
    public const int LIMIT = 20;

    /**
     * @param  list<OptionData>  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
