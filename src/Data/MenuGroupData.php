<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MenuGroupData extends Data
{
    /**
     * @param  list<MenuItemData>  $items
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public array $items,
    ) {}
}
