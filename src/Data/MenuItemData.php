<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MenuItemData extends Data
{
    public function __construct(
        public string $slug,
        public string $label,
        public string $icon,
        public string $href,
        // The `{slug}.create` URL when that route exists and the user may create.
        public ?string $create_href = null,
    ) {}
}
