<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The `levels` prop: every level, the protected one first. */
#[TypeScript]
final class LevelListData extends Data
{
    /**
     * @param  list<LevelListItemData>  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
